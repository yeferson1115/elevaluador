<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\IngresoImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class IngresoImageDeduplicationService
{
    /**
     * Elimina imágenes repetidas de un ingreso comparando el contenido real
     * del archivo, no el nombre ni la extensión.
     */
    public function eliminarDuplicadas(Ingreso $ingreso): int
    {
        $imagenes = IngresoImage::where('avaluo_id', $ingreso->id)
            ->orderBy('categoria')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $hashesVistos = [];
        $duplicadasEliminadas = 0;
        $registrosDuplicadosEliminados = 0;

        foreach ($imagenes as $imagen) {
            $rutaAbsoluta = $this->rutaAbsoluta($imagen->path);

            if (!is_file($rutaAbsoluta) || !is_readable($rutaAbsoluta)) {
                Log::warning('No se pudo validar la imagen del ingreso porque el archivo no existe o no es legible.', [
                    'ingreso_id' => $ingreso->id,
                    'image_id' => $imagen->id,
                    'path' => $imagen->path,
                ]);
                continue;
            }

            $hash = hash_file('sha256', $rutaAbsoluta);

            if (!isset($hashesVistos[$hash])) {
                $hashesVistos[$hash] = [
                    'id' => $imagen->id,
                    'path' => $this->rutaCanonica($rutaAbsoluta),
                ];
                continue;
            }

            if ($this->eliminarArchivoDuplicado($rutaAbsoluta, $hashesVistos[$hash]['path'], $ingreso->id, $imagen->id, $imagen->path)) {
                $duplicadasEliminadas++;
            }

            $imagen->delete();
            $registrosDuplicadosEliminados++;
        }

        if ($registrosDuplicadosEliminados > 0) {
            $ingreso->unsetRelation('images');
            $ingreso->load('images');
        }

        return $duplicadasEliminadas;
    }

    private function eliminarArchivoDuplicado(string $rutaAbsoluta, string $rutaPrincipal, int $ingresoId, int $imagenId, string $path): bool
    {
        if ($this->rutaCanonica($rutaAbsoluta) === $rutaPrincipal) {
            return false;
        }

        if (File::delete($rutaAbsoluta)) {
            return true;
        }

        Log::warning('No se pudo eliminar la imagen duplicada del servidor.', [
            'ingreso_id' => $ingresoId,
            'image_id' => $imagenId,
            'path' => $path,
        ]);

        return false;
    }

    private function rutaAbsoluta(string $path): string
    {
        return public_path(ltrim($path, '/'));
    }

    private function rutaCanonica(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
