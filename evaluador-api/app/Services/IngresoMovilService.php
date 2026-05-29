<?php

namespace App\Services;

use App\Models\Avaluo;
use App\Models\Ingreso;
use App\Models\IngresoImage;
use App\Models\Inspeccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class IngresoMovilService
{
    public function crearOEditarDesdeMovil(Request $request): array
    {
        $data = $request->validate([
            'client_id' => 'nullable|string|max:255',
            'placa' => 'required|string|max:20',
            'kilometraje' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'fecha_inspeccion' => 'nullable|date',
            'origen' => 'nullable|string|max:50',
            'tipo' => 'nullable|string|max:50',
            'tiposervicio' => 'nullable|string|max:50',
            'tipo_servicio' => 'nullable|string|max:50',
            'tipoServicio' => 'nullable|string|max:50',
            'categoria' => 'nullable|string|max:100',
            'imagenes' => 'nullable',
        ]);

        $tipoServicio = $this->normalizarTipoServicio(
            $data['tipo'] ?? $data['tiposervicio'] ?? $data['tipo_servicio'] ?? $data['tipoServicio'] ?? null
        );
        $placa = $this->normalizarPlaca($data['placa']);
        $kilometraje = $this->normalizarKilometraje($data['kilometraje'] ?? null);
        $fechaInspeccion = $this->normalizarFecha($data['fecha_inspeccion'] ?? null);
        $observaciones = $data['observaciones'] ?? null;
        $userId = auth()->id();

        return DB::transaction(function () use ($request, $tipoServicio, $placa, $kilometraje, $fechaInspeccion, $observaciones, $userId) {
            $ingreso = Ingreso::whereRaw('UPPER(placa) = ?', [$placa])->first();
            $creado = false;

            $ingresoData = array_filter([
                'tiposervicio' => $tipoServicio,
                'placa' => $placa,
                'kilometraje' => $kilometraje,
                'fecha_inspeccion' => $fechaInspeccion,
                'fecha_ingreso' => now()->toDateString(),
                'estado' => 'En Inspección',
                'codigo_interno_movil' => $request->input('client_id'),
            ], fn ($value) => $value !== null);

            if ($ingreso) {
                $ingreso->update($ingresoData);
            } else {
                $ingreso = Ingreso::create($ingresoData);
                $creado = true;
            }

            $avaluo = null;
            $inspeccion = null;

            if ($this->requiereAvaluo($tipoServicio)) {
                $avaluo = $this->crearOActualizarAvaluo($ingreso, $tipoServicio, $observaciones, $fechaInspeccion, $userId);
            }

            if ($this->requiereInspeccion($tipoServicio)) {
                $inspeccion = $this->crearOActualizarInspeccion($ingreso, $observaciones, $kilometraje, $userId);
            }

            $resultadoImagenes = $this->guardarImagenes(
                $ingreso,
                $request,
                $request->input('categoria', 'app_movil')
            );

            return [
                'created' => $creado,
                'ingreso' => $ingreso->fresh(['avaluo', 'inspeccion', 'images']),
                'avaluo' => $avaluo?->fresh(),
                'inspeccion' => $inspeccion?->fresh(),
                'imagenes' => $resultadoImagenes['imagenes'],
                'imagenes_advertencias' => $resultadoImagenes['advertencias'],
            ];
        });
    }

    private function normalizarTipoServicio(?string $tipo): string
    {
        $normalizado = mb_strtolower(trim((string) $tipo));
        $normalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalizado);

        return match ($normalizado) {
            'avaluo' => 'Avaluo',
            'inspeccion' => 'Inspección',
            'avaluo e inspeccion' => 'Avaluo e Inspección',
            'sec bogota' => 'Sec Bogota',
            default => throw ValidationException::withMessages([
                'tipo' => 'El tipo de servicio debe ser Avaluo, Inspección, Avaluo e Inspección o Sec Bogota.',
            ]),
        };
    }

    private function normalizarPlaca(string $placa): string
    {
        return mb_strtoupper(trim($placa));
    }

    private function normalizarKilometraje(?string $kilometraje): ?int
    {
        if ($kilometraje === null) {
            return null;
        }

        $soloNumeros = preg_replace('/\D+/', '', $kilometraje);

        return $soloNumeros === '' ? null : (int) $soloNumeros;
    }

    private function normalizarFecha(?string $fecha): ?string
    {
        return $fecha ? Carbon::parse($fecha)->toDateString() : null;
    }

    private function requiereAvaluo(string $tipoServicio): bool
    {
        return in_array($tipoServicio, ['Avaluo', 'Avaluo e Inspección', 'Sec Bogota'], true);
    }

    private function requiereInspeccion(string $tipoServicio): bool
    {
        return in_array($tipoServicio, ['Inspección', 'Avaluo e Inspección'], true);
    }

    private function crearOActualizarAvaluo(
        Ingreso $ingreso,
        string $tipoServicio,
        ?string $observaciones,
        ?string $fechaInspeccion,
        ?int $userId
    ): Avaluo {
        $avaluo = Avaluo::firstOrNew(['ingreso_id' => $ingreso->id]);
        $data = [
            'tipo' => $tipoServicio,
            'formato' => $tipoServicio === 'Sec Bogota' ? 'Sec Bogota' : 'El Avaluador',
            'observaciones' => $observaciones,
            'fecha_inspeccion' => $fechaInspeccion,
            'user_id' => $userId,
            'trabajado_movil' => true,
        ];

        if ($tipoServicio === 'Sec Bogota' && !$avaluo->exists && !$avaluo->code_movilidad) {
            $data['code_movilidad'] = $this->siguienteCodeMovilidad();
        }

        $avaluo->fill(array_filter($data, fn ($value) => $value !== null));
        $avaluo->save();

        return $avaluo;
    }

    private function crearOActualizarInspeccion(
        Ingreso $ingreso,
        ?string $observaciones,
        ?int $kilometraje,
        ?int $userId
    ): Inspeccion {
        $inspeccion = Inspeccion::firstOrNew(['ingreso_id' => $ingreso->id]);
        $inspeccion->fill(array_filter([
            'observaciones' => $observaciones,
            'kilometraje' => $kilometraje,
            'user_id' => $userId,
        ], fn ($value) => $value !== null));
        $inspeccion->save();

        return $inspeccion;
    }

    private function siguienteCodeMovilidad(): int
    {
        $ultimo = Avaluo::whereHas('ingreso', fn ($query) => $query->where('tiposervicio', 'Sec Bogota'))
            ->max('code_movilidad');

        return ((int) $ultimo) + 1;
    }

    /**
     * @return array{imagenes: array<int, array<string, mixed>>, advertencias: array<int, string>}
     */
    private function guardarImagenes(Ingreso $ingreso, Request $request, string $categoria): array
    {
        $archivos = $this->extraerArchivosImagen($request->allFiles());
        $entradas = $this->extraerEntradasImagen($request->all());
        $imagenes = [];
        $advertencias = [];
        $directory = "avaluos/{$ingreso->id}";
        $fullPath = public_path($directory);

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        foreach ($archivos as $archivo) {
            if (!$archivo->isValid()) {
                $advertencias[] = 'Una imagen no se pudo guardar porque el archivo subido no es válido.';
                continue;
            }

            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'jpg');

            if (!$this->extensionImagenPermitida($extension)) {
                $advertencias[] = "La imagen {$archivo->getClientOriginalName()} fue omitida porque su tipo no está permitido.";
                continue;
            }

            $imagenes[] = $this->crearRegistroImagen(
                $ingreso,
                $categoria,
                $directory,
                $fullPath,
                $extension,
                fn (string $rutaCompleta) => $archivo->move(dirname($rutaCompleta), basename($rutaCompleta))
            );
        }

        foreach ($this->extraerImagenesBase64($entradas) as $imagenBase64) {
            $imagenes[] = $this->crearRegistroImagen(
                $ingreso,
                $categoria,
                $directory,
                $fullPath,
                $imagenBase64['extension'],
                fn (string $rutaCompleta) => file_put_contents($rutaCompleta, $imagenBase64['contenido'])
            );
        }

        $entradasInvalidas = $this->contarEntradasImagenInvalidas($entradas);

        if ($entradasInvalidas > 0 && count($archivos) === 0) {
            $advertencias[] = 'Las imágenes llegaron como [object Object] o texto sin binario. El backend solo puede guardar archivos multipart reales, base64/data URL o URLs http(s) descargables.';
        }

        return [
            'imagenes' => $imagenes,
            'advertencias' => $advertencias,
        ];
    }

    private function extraerEntradasImagen(array $datos): mixed
    {
        $entradas = [];

        foreach ($datos as $clave => $valor) {
            if ($this->esClaveImagen((string) $clave)) {
                $entradas[] = $valor;
            }
        }

        return $entradas;
    }

    private function esClaveImagen(string $clave): bool
    {
        $clave = mb_strtolower($clave);

        return str_contains($clave, 'imagen')
            || str_contains($clave, 'image')
            || str_contains($clave, 'foto')
            || str_contains($clave, 'photo');
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function extraerArchivosImagen(mixed $entrada): array
    {
        if ($entrada instanceof UploadedFile) {
            return [$entrada];
        }

        if (!is_array($entrada)) {
            return [];
        }

        $archivos = [];

        foreach ($entrada as $item) {
            $archivos = array_merge($archivos, $this->extraerArchivosImagen($item));
        }

        return $archivos;
    }

    /**
     * @return array<int, array{contenido: string, extension: string}>
     */
    private function extraerImagenesBase64(mixed $entrada): array
    {
        if (is_string($entrada)) {
            $entrada = trim($entrada);
            $json = json_decode($entrada, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->extraerImagenesBase64($json);
            }

            $imagen = $this->decodificarImagenBase64($entrada)
                ?? $this->descargarImagenDesdeUrl($entrada);

            return $imagen ? [$imagen] : [];
        }

        if (!is_array($entrada)) {
            return [];
        }

        $imagenes = [];

        if (isset($entrada['base64']) || isset($entrada['data']) || isset($entrada['uri']) || isset($entrada['url'])) {
            $contenido = $entrada['base64'] ?? $entrada['data'] ?? $entrada['uri'] ?? $entrada['url'];

            if (is_string($contenido)) {
                $imagen = $this->decodificarImagenBase64($contenido, $entrada['type'] ?? null)
                    ?? $this->descargarImagenDesdeUrl($contenido);

                if ($imagen) {
                    return [$imagen];
                }
            }
        }

        foreach ($entrada as $item) {
            $imagenes = array_merge($imagenes, $this->extraerImagenesBase64($item));
        }

        return $imagenes;
    }

    /**
     * @return array{contenido: string, extension: string}|null
     */
    private function decodificarImagenBase64(string $valor, ?string $mimeSugerido = null): ?array
    {
        $valor = trim($valor);
        $mime = $mimeSugerido;

        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $valor, $matches)) {
            $mime = $matches[1];
            $valor = $matches[2];
        }

        if ($valor === '' || $valor === '[object Object]' || !preg_match('/^[A-Za-z0-9+\/\r\n=]+$/', $valor)) {
            return null;
        }

        $contenido = base64_decode($valor, true);

        if ($contenido === false || strlen($contenido) > 10 * 1024 * 1024) {
            return null;
        }

        $extension = $this->extensionDesdeMime($mime) ?? $this->extensionDesdeContenido($contenido);

        if (!$extension || !$this->extensionImagenPermitida($extension)) {
            return null;
        }

        return [
            'contenido' => $contenido,
            'extension' => $extension,
        ];
    }

    /**
     * @return array{contenido: string, extension: string}|null
     */
    private function descargarImagenDesdeUrl(string $url): ?array
    {
        if (!preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $contenido = $response->body();

        if ($contenido === '' || strlen($contenido) > 10 * 1024 * 1024) {
            return null;
        }

        $extension = $this->extensionDesdeMime($response->header('Content-Type'))
            ?? $this->extensionDesdeContenido($contenido);

        if (!$extension || !$this->extensionImagenPermitida($extension)) {
            return null;
        }

        return [
            'contenido' => $contenido,
            'extension' => $extension,
        ];
    }

    private function crearRegistroImagen(
        Ingreso $ingreso,
        string $categoria,
        string $directory,
        string $fullPath,
        string $extension,
        callable $guardarArchivo
    ): array {
        $filename = uniqid('movil_', true) . '.' . strtolower($extension);
        $rutaCompleta = "{$fullPath}/{$filename}";
        $guardarArchivo($rutaCompleta);
        $relativePath = "{$directory}/{$filename}";

        $imagen = IngresoImage::create([
            'avaluo_id' => $ingreso->id,
            'categoria' => $categoria,
            'path' => $relativePath,
            'orden' => ((int) IngresoImage::where('avaluo_id', $ingreso->id)
                ->where('categoria', $categoria)
                ->max('orden')) + 1,
            'rotacion' => 0,
        ]);

        return [
            'id' => $imagen->id,
            'categoria' => $imagen->categoria,
            'orden' => $imagen->orden,
            'rotacion' => $imagen->rotacion,
            'url' => asset($relativePath),
        ];
    }

    private function contarEntradasImagenInvalidas(mixed $entrada): int
    {
        if ($entrada instanceof UploadedFile) {
            return 0;
        }

        if (is_string($entrada)) {
            return $this->extraerImagenesBase64($entrada) ? 0 : 1;
        }

        if (!is_array($entrada)) {
            return $entrada === null ? 0 : 1;
        }

        if (isset($entrada['base64']) || isset($entrada['data'])) {
            return $this->extraerImagenesBase64($entrada) ? 0 : 1;
        }

        return array_sum(array_map(fn ($item) => $this->contarEntradasImagenInvalidas($item), $entrada));
    }

    private function extensionImagenPermitida(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpeg', 'jpg', 'png', 'gif', 'webp'], true);
    }

    private function extensionDesdeMime(?string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    private function extensionDesdeContenido(string $contenido): ?string
    {
        $info = @getimagesizefromstring($contenido);

        if (!$info || !isset($info['mime'])) {
            return null;
        }

        return $this->extensionDesdeMime($info['mime']);
    }
}
