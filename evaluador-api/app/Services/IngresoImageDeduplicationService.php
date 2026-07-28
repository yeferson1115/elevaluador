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

        $firmasVistas = [];
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

            $firma = $this->firmaImagen($rutaAbsoluta);
            $duplicadaDe = $this->buscarImagenDuplicada($firma, $firmasVistas);

            if (!$duplicadaDe) {
                $firmasVistas[] = $firma;
                continue;
            }

            if ($this->eliminarArchivoDuplicado($rutaAbsoluta, $duplicadaDe['path'], $ingreso->id, $imagen->id, $imagen->path)) {
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

    /**
     * Genera una firma exacta y una firma visual para detectar archivos iguales
     * aunque hayan sido guardados con nombres o compresiones diferentes.
     *
     * @return array{hash: string, perceptual_hash: ?string, path: string}
     */
    private function firmaImagen(string $rutaAbsoluta): array
    {
        return [
            'hash' => hash_file('sha256', $rutaAbsoluta),
            'perceptual_hash' => $this->hashPerceptual($rutaAbsoluta),
            'path' => $this->rutaCanonica($rutaAbsoluta),
        ];
    }

    /**
     * @param array{hash: string, perceptual_hash: ?string, path: string} $firma
     * @param array<int, array{hash: string, perceptual_hash: ?string, path: string}> $firmasVistas
     * @return array{hash: string, perceptual_hash: ?string, path: string}|null
     */
    private function buscarImagenDuplicada(array $firma, array $firmasVistas): ?array
    {
        foreach ($firmasVistas as $firmaVista) {
            if ($firma['hash'] === $firmaVista['hash']) {
                return $firmaVista;
            }

            if (
                $firma['perceptual_hash'] !== null
                && $firmaVista['perceptual_hash'] !== null
                && $this->distanciaHamming($firma['perceptual_hash'], $firmaVista['perceptual_hash']) <= 6
            ) {
                return $firmaVista;
            }
        }

        return null;
    }

    private function hashPerceptual(string $rutaAbsoluta): ?string
    {
        $imagen = $this->crearImagenDesdeArchivo($rutaAbsoluta);

        if (!$imagen) {
            return null;
        }

        $miniatura = imagecreatetruecolor(8, 8);

        if (!$miniatura) {
            imagedestroy($imagen);
            return null;
        }

        imagecopyresampled(
            $miniatura,
            $imagen,
            0,
            0,
            0,
            0,
            8,
            8,
            imagesx($imagen),
            imagesy($imagen)
        );

        $valores = [];
        $total = 0;

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($miniatura, $x, $y);
                $rojo = ($rgb >> 16) & 0xFF;
                $verde = ($rgb >> 8) & 0xFF;
                $azul = $rgb & 0xFF;
                $gris = (int) round(($rojo + $verde + $azul) / 3);
                $valores[] = $gris;
                $total += $gris;
            }
        }

        imagedestroy($miniatura);
        imagedestroy($imagen);

        $promedio = $total / count($valores);

        return implode('', array_map(fn (int $valor) => $valor >= $promedio ? '1' : '0', $valores));
    }

    private function crearImagenDesdeArchivo(string $rutaAbsoluta)
    {
        $tipo = function_exists('exif_imagetype')
            ? @exif_imagetype($rutaAbsoluta)
            : ($this->tipoImagenPorGetimagesize($rutaAbsoluta));

        return match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($rutaAbsoluta),
            IMAGETYPE_PNG => @imagecreatefrompng($rutaAbsoluta),
            IMAGETYPE_GIF => @imagecreatefromgif($rutaAbsoluta),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($rutaAbsoluta) : null,
            default => null,
        };
    }

    private function tipoImagenPorGetimagesize(string $rutaAbsoluta): ?int
    {
        $informacion = @getimagesize($rutaAbsoluta);

        return $informacion[2] ?? null;
    }

    private function distanciaHamming(string $hashA, string $hashB): int
    {
        if (strlen($hashA) !== strlen($hashB)) {
            return PHP_INT_MAX;
        }

        $distancia = 0;

        for ($i = 0; $i < strlen($hashA); $i++) {
            if ($hashA[$i] !== $hashB[$i]) {
                $distancia++;
            }
        }

        return $distancia;
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
