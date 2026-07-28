<?php

namespace App\Services;

use App\Models\Ingreso;
use Illuminate\Support\Facades\Log;

class IngresoImageDeduplicationService
{
    /**
     * Elimina imágenes repetidas de un ingreso comparando el contenido real
     * del archivo, no el nombre ni la extensión.
     */
    public function eliminarDuplicadas(Ingreso $ingreso): int
    {
        $ingreso->loadMissing('images');

        $hashesVistos = [];
        $duplicadasEliminadas = 0;
        $registrosDuplicadosEliminados = 0;

        foreach ($ingreso->images as $imagen) {
            $rutaAbsoluta = public_path($imagen->path);

            if (!is_file($rutaAbsoluta) || !is_readable($rutaAbsoluta)) {
                continue;
            }

            $hash = hash_file('sha256', $rutaAbsoluta);

            if (!isset($hashesVistos[$hash])) {
                $hashesVistos[$hash] = [
                    'id' => $imagen->id,
                    'path' => realpath($rutaAbsoluta) ?: $rutaAbsoluta,
                ];
                continue;
            }

            $rutaReal = realpath($rutaAbsoluta) ?: $rutaAbsoluta;
            $rutaPrincipal = $hashesVistos[$hash]['path'];

            if ($rutaReal !== $rutaPrincipal) {
                if (@unlink($rutaAbsoluta)) {
                    $duplicadasEliminadas++;
                } else {
                    Log::warning('No se pudo eliminar la imagen duplicada del servidor.', [
                        'ingreso_id' => $ingreso->id,
                        'image_id' => $imagen->id,
                        'path' => $imagen->path,
                    ]);
                }
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
}
