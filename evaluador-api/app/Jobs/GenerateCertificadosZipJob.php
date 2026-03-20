<?php

namespace App\Jobs;

use App\Mail\CertificadosZipListoMail;
use App\Models\Ingreso;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GenerateCertificadosZipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(
        private readonly int $userId,
        private readonly ?string $filtro = '',
        private readonly array $ids = [],
        private readonly bool $exportaTodosFiltrados = false,
    ) {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $filtro = trim((string) ($this->filtro ?? ''));

        $user = User::find($this->userId);

        if (! $user || empty($user->email)) {
            Log::warning('No se pudo generar el ZIP en segundo plano: usuario sin email.', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        ini_set('max_execution_time', '7200');
        ini_set('memory_limit', '4096M');

        $query = Ingreso::with([
            'avaluo' => function ($query) {
                $query->whereNotNull('file')
                    ->where('file', '!=', '');
            },
            'avaluo.clasificados',
            'avaluo.corregidos',
            'avaluo.limitaciones',
            'images',
        ]);

        $this->aplicarFiltroExportacion($query, 'Sec Bogota', $filtro, $this->ids);
        $ingresos = $query->get();

        if ($ingresos->isEmpty()) {
            Mail::to($user->email)->send(new CertificadosZipListoMail(
                userName: $user->name,
                totalRegistros: 0,
                downloadUrl: null,
                zipFileName: null,
                exportaTodosFiltrados: $this->exportaTodosFiltrados,
                filtro: $filtro,
                errores: ['No hay certificados con PDF disponible para el filtro solicitado.'],
            ));
            return;
        }

        $timestamp = now()->format('Y-m-d-H-i-s');
        $random = Str::lower(Str::random(10));
        $zipFileName = "certificados-sec-bogota-{$timestamp}-{$random}.zip";
        $relativePath = 'exports/' . $zipFileName;
        $absolutePath = storage_path('app/public/' . $relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($openResult !== true) {
            throw new \RuntimeException('No fue posible crear el archivo ZIP. Código: ' . $openResult);
        }

        $archivosAgregados = 0;
        $errores = [];

        foreach ($ingresos as $ingreso) {
            try {
                if (! $ingreso->avaluo || ! $this->validarDatosAvaluo($ingreso)) {
                    $errores[] = 'Se omitió la placa ' . ($ingreso->placa ?? 'sin placa') . ' por información incompleta.';
                    continue;
                }

                $pdf = $this->generarPdfParaZip($ingreso);

                if (! $pdf) {
                    $errores[] = 'No fue posible generar el PDF para la placa ' . ($ingreso->placa ?? 'sin placa') . '.';
                    continue;
                }
                $pdfContent = $pdf->output();
                if (! $pdfContent) {
                    $errores[] = 'El PDF de la placa ' . ($ingreso->placa ?? 'sin placa') . ' quedó vacío.';
                    continue;
                }

                if ($zip->addFromString($this->generarNombreArchivoZip($ingreso), $pdfContent)) {
                    $archivosAgregados++;
                }
            } catch (\Throwable $e) {
                Log::error('Error generando ZIP de certificados en segundo plano.', [
                    'ingreso_id' => $ingreso->id,
                    'placa' => $ingreso->placa,
                    'error' => $e->getMessage(),
                ]);

                $errores[] = 'Error al procesar la placa ' . ($ingreso->placa ?? 'sin placa') . ': ' . $e->getMessage();
            }
        }

        $zip->close();

        if ($archivosAgregados === 0) {
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }

            Mail::to($user->email)->send(new CertificadosZipListoMail(
                userName: $user->name,
                totalRegistros: $ingresos->count(),
                downloadUrl: null,
                zipFileName: null,
                exportaTodosFiltrados: $this->exportaTodosFiltrados,
                filtro: $filtro,
                errores: $errores,
            ));
            return;
        }

        $downloadUrl = rtrim(config('app.url'), '/') . Storage::url($relativePath);

        Mail::to($user->email)->send(new CertificadosZipListoMail(
            userName: $user->name,
            totalRegistros: $archivosAgregados,
            downloadUrl: $downloadUrl,
            zipFileName: $zipFileName,
            exportaTodosFiltrados: $this->exportaTodosFiltrados,
            filtro: $filtro,
            errores: array_slice($errores, 0, 10),
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falló definitivamente la generación del ZIP de certificados.', [
            'user_id' => $this->userId,
            'filtro' => $this->filtro,
            'ids_count' => count($this->ids),
            'exporta_todos_filtrados' => $this->exportaTodosFiltrados,
            'error' => $exception->getMessage(),
        ]);
    }

    private function aplicarFiltroExportacion($query, string $tiposervicio, ?string $filtro = '', array $ids = [])
    {
        $filtro = trim((string) ($filtro ?? ''));

        $query->where('tiposervicio', $tiposervicio)
            ->whereHas('avaluo', function ($q) {
                $q->whereNotNull('file')
                    ->where('file', '!=', '');
            });

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        if ($filtro) {
            $query->where(function ($q) use ($filtro) {
                $q->where('placa', 'like', '%' . $filtro . '%')
                    ->orWhere('solicitante', 'like', '%' . $filtro . '%')
                    ->orWhere('ubicacion_activo', 'like', '%' . $filtro . '%')
                    ->orWhereHas('avaluo', function ($subQuery) use ($filtro) {
                        $subQuery->where('evaluador', 'like', '%' . $filtro . '%');
                    });
            });
        }

        return $query;
    }

    private function validarDatosAvaluo(Ingreso $ingreso): bool
    {
        if (! $ingreso->avaluo) {
            return false;
        }

        $camposRequeridos = [
            $ingreso->placa,
            $ingreso->marca,
            $ingreso->modelo,
            $ingreso->avaluo->valor_razonable,
        ];

        foreach ($camposRequeridos as $valor) {
            if (empty($valor) && $valor !== 0 && $valor !== '0') {
                return false;
            }
        }

        return true;
    }

    private function generarNombreArchivoZip(Ingreso $ingreso): string
    {
        $placaLimpia = preg_replace('/[^a-zA-Z0-9]/', '_', $ingreso->placa ?? 'sin_placa');
        $inicial = $ingreso->avaluo->inicial ?? '';
        $consecutivo = $ingreso->avaluo->consecutivo ?? '';

        if ($inicial !== '' && $consecutivo !== '') {
            return "{$placaLimpia}-{$inicial}{$consecutivo}.pdf";
        }

        if (! empty($ingreso->avaluo->file)) {
            $nombreArchivo = basename($ingreso->avaluo->file);
            if (! str_ends_with(strtolower($nombreArchivo), '.pdf')) {
                $nombreArchivo .= '.pdf';
            }

            return "{$placaLimpia}_{$nombreArchivo}";
        }

        return "{$placaLimpia}.pdf";
    }

    private function generarPdfParaZip(Ingreso $ingreso)
    {
        $avaluo = $ingreso->avaluo;
        if (! $avaluo) {
            return null;
        }

        $user = User::find($avaluo->user_id);
        $graficaPath = null;
        $resultado = null;

        if (in_array($avaluo->tipo, ['comercial', 'jans'], true) && $avaluo->corregidos && $avaluo->corregidos->isNotEmpty()) {
            $corregidos = collect($avaluo->corregidos)->map(fn ($c) => [
                'x' => (int) $c->modelo,
                'y' => (float) $c->valor,
            ])->toArray();

            $resultado = $this->calcularExponencial($corregidos, (int) $ingreso->modelo);
        }

        if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === 'Sec Bogota') {
            return Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        }

        if (in_array($avaluo->tipo, ['comercial', 'jans'], true)) {
            return Pdf::loadView('pdf.avaluojans', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        }

        return Pdf::loadView('pdf.avaluo', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
    }

    private function calcularExponencial(array $puntos, int $modeloConsultar): ?array
    {
        $n = count($puntos);
        if ($n < 2) {
            return null;
        }

        $sumX = 0;
        $sumLnY = 0;
        $sumX2 = 0;
        $sumXlnY = 0;

        foreach ($puntos as $punto) {
            if (($punto['y'] ?? 0) <= 0) {
                continue;
            }

            $x = (float) $punto['x'];
            $lnY = log((float) $punto['y']);

            $sumX += $x;
            $sumLnY += $lnY;
            $sumX2 += $x * $x;
            $sumXlnY += $x * $lnY;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0.0) {
            return null;
        }

        $b = (($n * $sumXlnY) - ($sumX * $sumLnY)) / $denominator;
        $lnA = ($sumLnY - ($b * $sumX)) / $n;
        $a = exp($lnA);
        $estimado = $a * exp($b * $modeloConsultar);

        return [
            'a' => $a,
            'b' => $b,
            'valor' => round($estimado, 2),
        ];
    }
}
