<?php

namespace App\Jobs;

use App\Mail\CertificadosZipListoMail;
use App\Models\Ingreso;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        $this->onQueue('exports');
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

                unset($pdf, $pdfContent);
                gc_collect_cycles();
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

        $avaluo->loadMissing(['clasificados', 'corregidos', 'limitaciones']);

        $user = User::find($avaluo->user_id);
        $graficaPath = null;
        $resultado = null;

        if (in_array($avaluo->tipo, ['comercial', 'jans'], true)) {
            $graficaPath = $this->generarGraficaDispercionOptimizada($avaluo);
        }

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

    private function generarGraficaDispercionOptimizada($avaluo): ?string
    {
        $cacheKey = "grafica_avaluo_{$avaluo->id}_" . md5(json_encode([
            'clasificados_count' => $avaluo->clasificados?->count() ?? 0,
            'corregidos_count' => $avaluo->corregidos?->count() ?? 0,
            'ultima_actualizacion' => (string) $avaluo->updated_at,
        ]));

        $cachedFilename = Cache::get($cacheKey);
        if ($cachedFilename) {
            $cachedPath = public_path("graficas/{$cachedFilename}");
            if (file_exists($cachedPath)) {
                return $cachedFilename;
            }
        }

        $clasificados = collect($avaluo->clasificados)->take(30)->map(function ($item) {
            return [
                'x' => is_numeric($item->modelo) ? (float) $item->modelo : 0.0,
                'y' => is_numeric($item->valor) ? (float) $item->valor : 0.0,
            ];
        })->filter(fn ($punto) => $punto['y'] > 0)->values()->all();

        $corregidos = collect($avaluo->corregidos)->take(30)->map(function ($item) {
            return [
                'x' => is_numeric($item->modelo) ? (float) $item->modelo : 0.0,
                'y' => is_numeric($item->valor) ? (float) $item->valor : 0.0,
            ];
        })->filter(fn ($punto) => $punto['y'] > 0)->values()->all();

        if (empty($clasificados) && empty($corregidos)) {
            return null;
        }

        $datasets = [];

        if (! empty($clasificados)) {
            $datasets[] = [
                'label' => 'Clasificados',
                'data' => $clasificados,
                'backgroundColor' => 'rgba(54,162,235,0.8)',
                'borderColor' => 'rgba(54,162,235,1)',
                'showLine' => false,
                'pointRadius' => 3,
            ];
        }

        if (! empty($corregidos)) {
            $datasets[] = [
                'label' => 'Corregidos',
                'data' => $corregidos,
                'backgroundColor' => 'rgba(255,99,132,0.8)',
                'borderColor' => 'rgba(255,99,132,1)',
                'showLine' => false,
                'pointRadius' => 3,
            ];
        }

        $regresionClasificados = $this->calcularRegresionRapida($clasificados);
        if ($regresionClasificados) {
            $datasets[] = [
                'label' => 'f(x) Clasificados',
                'data' => $regresionClasificados['curve'],
                'type' => 'line',
                'borderColor' => 'rgba(54,162,235,1)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0,
                'borderWidth' => 1,
            ];
        }

        $regresionCorregidos = $this->calcularRegresionRapida($corregidos);
        if ($regresionCorregidos) {
            $datasets[] = [
                'label' => 'f(x) Corregidos',
                'data' => $regresionCorregidos['curve'],
                'type' => 'line',
                'borderColor' => 'rgba(255,99,132,1)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0,
                'borderWidth' => 1,
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->connectTimeout(5)
                ->post('https://quickchart.io/chart', [
                    'chart' => [
                        'type' => 'scatter',
                        'data' => ['datasets' => $datasets],
                        'options' => [
                            'plugins' => [
                                'legend' => ['display' => true, 'position' => 'top'],
                                'title' => ['display' => false],
                            ],
                            'scales' => [
                                'x' => ['title' => ['display' => true, 'text' => 'Modelo']],
                                'y' => ['title' => ['display' => true, 'text' => 'Valor']],
                            ],
                            'responsive' => false,
                        ],
                    ],
                    'width' => 600,
                    'height' => 400,
                    'format' => 'png',
                    'version' => '4',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $filename = "avaluo_{$avaluo->id}.png";
            $filepath = public_path("graficas/{$filename}");
            if (! is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0777, true);
            }

            file_put_contents($filepath, $response->body());
            Cache::put($cacheKey, $filename, now()->addHours(24));

            return $filename;
        } catch (\Throwable $exception) {
            Log::warning('No fue posible generar la gráfica para el ZIP.', [
                'avaluo_id' => $avaluo->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function calcularRegresionRapida(array $puntos): ?array
    {
        if (count($puntos) < 2) {
            return null;
        }

        $puntosValidos = array_values(array_filter($puntos, fn ($punto) => ($punto['y'] ?? 0) > 0));
        if (count($puntosValidos) < 2) {
            return null;
        }

        $n = count($puntosValidos);
        $sumX = 0.0;
        $sumLnY = 0.0;
        $sumX2 = 0.0;
        $sumXlnY = 0.0;

        foreach ($puntosValidos as $punto) {
            $x = (float) $punto['x'];
            $lnY = log((float) $punto['y']);
            $sumX += $x;
            $sumLnY += $lnY;
            $sumX2 += $x * $x;
            $sumXlnY += $x * $lnY;
        }

        $denominador = ($n * $sumX2) - ($sumX * $sumX);
        if (abs($denominador) < 1e-12) {
            return null;
        }

        $b = (($n * $sumXlnY) - ($sumX * $sumLnY)) / $denominador;
        $lnA = ($sumLnY - ($b * $sumX)) / $n;
        $a = exp($lnA);

        $xs = array_column($puntosValidos, 'x');
        sort($xs);
        $minX = (float) reset($xs);
        $maxX = (float) end($xs);

        if ($minX === $maxX) {
            return null;
        }

        $curve = [];
        $steps = min(25, max(10, count($xs)));
        $step = ($maxX - $minX) / ($steps - 1);

        for ($i = 0; $i < $steps; $i++) {
            $x = $minX + ($step * $i);
            $curve[] = [
                'x' => round($x, 2),
                'y' => round($a * exp($b * $x), 2),
            ];
        }

        return ['curve' => $curve];
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
