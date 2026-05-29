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
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'file|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
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

            $imagenes = $this->guardarImagenes(
                $ingreso,
                $request->file('imagenes', []),
                $request->input('categoria', 'app_movil')
            );

            return [
                'created' => $creado,
                'ingreso' => $ingreso->fresh(['avaluo', 'inspeccion', 'images']),
                'avaluo' => $avaluo?->fresh(),
                'inspeccion' => $inspeccion?->fresh(),
                'imagenes' => $imagenes,
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
     * @param array<int, UploadedFile>|UploadedFile|null $archivos
     */
    private function guardarImagenes(Ingreso $ingreso, array|UploadedFile|null $archivos, string $categoria): array
    {
        if ($archivos instanceof UploadedFile) {
            $archivos = [$archivos];
        }

        $archivos = $archivos ?: [];
        $imagenes = [];
        $directory = "avaluos/{$ingreso->id}";
        $fullPath = public_path($directory);

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        foreach ($archivos as $archivo) {
            if (!$archivo instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'jpg');
            $filename = uniqid('movil_', true) . '.' . $extension;
            $archivo->move($fullPath, $filename);
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

            $imagenes[] = [
                'id' => $imagen->id,
                'categoria' => $imagen->categoria,
                'orden' => $imagen->orden,
                'rotacion' => $imagen->rotacion,
                'url' => asset($relativePath),
            ];
        }

        return $imagenes;
    }
}
