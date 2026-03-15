<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\IngresoImage;
use App\Models\Avaluo;
use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use Barryvdh\DomPDF\Facade\Pdf;

class IngresoImageController extends Controller
{
    public function index($avaluoId)
    {
        $imagenes = IngresoImage::where('avaluo_id', $avaluoId)->get()
            ->map(fn($img) => [
                'id' => $img->id,
                'categoria' => $img->categoria,
                // Construimos la URL accesible desde public/
                'url' => asset($img->path),
            ]);
            $ingreso=Ingreso::find($avaluoId);

        return response()->json(['imagenes'=>$imagenes,'ingreso'=>$ingreso]);
    }

  public function store(Request $request, $avaluoId)
{
    $request->validate([
        'categoria' => 'required|string',
        'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
    ]);

    $imagenes = [];
    $directory = "avaluos/$avaluoId";
    $fullPath = public_path($directory);
    
    if (!File::exists($fullPath)) {
        File::makeDirectory($fullPath, 0755, true);
    }

    foreach ($request->file('imagenes', []) as $file) {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid() . '.' . $this->determinarExtensionOptima($extension);
        
        // Optimizar imagen usando GD nativo
        $optimized = $this->optimizarImagenGD(
            $file->getRealPath(),
            $extension,
            1920, // Ancho máximo
            1080, // Alto máximo
            90    // Calidad
        );
        
        // Guardar imagen optimizada
        $rutaCompleta = $fullPath . '/' . $filename;
        $this->guardarImagenOptimizada($optimized, $rutaCompleta, $filename);
        
        $relativePath = "$directory/$filename";
        
        $img = IngresoImage::create([
            'avaluo_id' => $avaluoId,
            'categoria' => $request->categoria,
            'path' => $relativePath,
        ]);

        $imagenes[] = [
            'id' => $img->id,
            'categoria' => $img->categoria,
            'url' => asset($relativePath),
            'peso_original' => $this->formatearBytes($file->getSize()),
            'peso_optimizado' => $this->formatearBytes(filesize($rutaCompleta)),
        ];
    }

    return response()->json(['imagenes' => $imagenes]);
}

private function determinarExtensionOptima($extensionOriginal)
{
    // Si el servidor soporta WebP (PHP 7.1+ con GD compilado con WebP)
    if (function_exists('imagewebp')) {
        return 'webp';
    }
    
    // Mantener JPEG para mejor compresión
    return $extensionOriginal === 'png' ? 'jpg' : $extensionOriginal;
}

private function optimizarImagenGD($rutaTemporal, $extension, $maxWidth, $maxHeight, $calidad)
{
    // Crear imagen desde archivo temporal
    switch($extension) {
        case 'jpeg':
        case 'jpg':
            $imagen = imagecreatefromjpeg($rutaTemporal);
            break;
        case 'png':
            $imagen = imagecreatefrompng($rutaTemporal);
            break;
        case 'gif':
            $imagen = imagecreatefromgif($rutaTemporal);
            break;
        default:
            return null;
    }
    
    if (!$imagen) {
        return null;
    }
    
    // Obtener dimensiones originales
    $anchoOriginal = imagesx($imagen);
    $altoOriginal = imagesy($imagen);
    
    // Calcular nuevas dimensiones manteniendo proporción
    list($nuevoAncho, $nuevoAlto) = $this->calcularRedimension(
        $anchoOriginal, 
        $altoOriginal, 
        $maxWidth, 
        $maxHeight
    );
    
    // Crear nueva imagen con dimensiones optimizadas
    $imagenOptimizada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    
    // Preservar transparencia para PNG
    if ($extension === 'png') {
        imagealphablending($imagenOptimizada, false);
        imagesavealpha($imagenOptimizada, true);
        $transparente = imagecolorallocatealpha($imagenOptimizada, 0, 0, 0, 127);
        imagefill($imagenOptimizada, 0, 0, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled(
        $imagenOptimizada, $imagen,
        0, 0, 0, 0,
        $nuevoAncho, $nuevoAlto,
        $anchoOriginal, $altoOriginal
    );
    
    // Limpiar memoria de imagen original
    imagedestroy($imagen);
    
    return $imagenOptimizada;
}

private function calcularRedimension($ancho, $alto, $maxAncho, $maxAlto)
{
    if ($ancho <= $maxAncho && $alto <= $maxAlto) {
        return [$ancho, $alto];
    }
    
    $ratio = $ancho / $alto;
    
    if ($ancho > $maxAncho) {
        $ancho = $maxAncho;
        $alto = $ancho / $ratio;
    }
    
    if ($alto > $maxAlto) {
        $alto = $maxAlto;
        $ancho = $alto * $ratio;
    }
    
    return [(int)$ancho, (int)$alto];
}

private function guardarImagenOptimizada($imagenGD, $rutaDestino, $nombreArchivo)
{
    $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    
    switch($extension) {
        case 'jpg':
        case 'jpeg':
            // Optimizar JPEG con calidad ajustada
            imagejpeg($imagenGD, $rutaDestino, 80);
            // Intentar compresión adicional si existe la función
            if (function_exists('exif_imagetype')) {
                $this->comprimirJpegAdicional($rutaDestino);
            }
            break;
            
        case 'png':
            // PNG con compresión máxima
            imagepng($imagenGD, $rutaDestino, 9);
            break;
            
        case 'webp':
            if (function_exists('imagewebp')) {
                imagewebp($imagenGD, $rutaDestino, 80);
            } else {
                // Fallback a JPEG si no hay soporte WebP
                imagejpeg($imagenGD, str_replace('.webp', '.jpg', $rutaDestino), 80);
            }
            break;
            
        case 'gif':
            imagegif($imagenGD, $rutaDestino);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($imagenGD);
    
    // Comprimir más si es posible
    $this->optimizarPostGuardado($rutaDestino);
}

private function comprimirJpegAdicional($ruta)
{
    // Intentar usar jpegoptim si está disponible en el sistema
    if (function_exists('exec') && `which jpegoptim`) {
        exec("jpegoptim --strip-all --max=80 " . escapeshellarg($ruta));
    }
}

private function optimizarPostGuardado($ruta)
{
    $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    
    if (!function_exists('exec')) {
        return;
    }
    
    // Usar herramientas del sistema si están disponibles
    switch($extension) {
        case 'png':
            if (`which optipng`) {
                exec("optipng -o2 " . escapeshellarg($ruta));
            } elseif (`which pngquant`) {
                exec("pngquant --force --ext .png " . escapeshellarg($ruta));
            }
            break;
            
        case 'jpg':
        case 'jpeg':
            if (`which jpegoptim`) {
                exec("jpegoptim --strip-all --max=80 " . escapeshellarg($ruta));
            }
            break;
            
        case 'webp':
            if (`which cwebp`) {
                $temp = $ruta . '.temp';
                exec("cwebp -q 80 " . escapeshellarg($ruta) . " -o " . escapeshellarg($temp));
                if (file_exists($temp)) {
                    unlink($ruta);
                    rename($temp, $ruta);
                }
            }
            break;
    }
}

private function formatearBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
    public function delete(Request $request, $avaluoId)
    {
        $request->validate([
            'categoria' => 'required|string',
            'url' => 'required|string',
        ]);

        // Extraemos la ruta relativa quitando el asset() y dominio
        $baseUrl = asset('');
        $relativePath = str_replace($baseUrl, '', $request->url);

        $image = IngresoImage::where('avaluo_id', $avaluoId)
            ->where('categoria', $request->categoria)
            ->where('path', $relativePath)
            ->first();

        if ($image) {
            $fullPath = public_path($image->path);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
            $image->delete();
        }

        return response()->json(['success' => true]);
    }

private function generarGraficaDispercion(Avaluo $avaluo)
{
    // Convertir colecciones a arrays simples
    $rawClas = $avaluo->clasificados->map(fn($c) => ['modelo' => $c->modelo, 'valor' => $c->valor])->toArray();
    $rawCorr = $avaluo->corregidos->map(fn($c) => ['modelo' => $c->modelo, 'valor' => $c->valor])->toArray();

    // Recolectar todos los modelos numéricos disponibles
    $allNumericModels = collect(array_merge($rawClas, $rawCorr))
        ->map(fn($r) => is_numeric($r['modelo']) ? (float)$r['modelo'] : null)
        ->filter()
        ->values();

    $hasNumericModels = $allNumericModels->isNotEmpty();
    $minNumericModel = $hasNumericModels ? $allNumericModels->min() : 1.0;

    // Helper para mapear datos a {x,y} con fallback cuando modelo no es numérico
    $mapSeries = function(array $items, float $startOffset) use ($hasNumericModels, $minNumericModel) {
        $out = [];
        $fallbackCounter = 0;
        foreach ($items as $it) {
            $rawModel = $it['modelo'] ?? null;
            $rawValor = $it['valor'] ?? null;

            if (is_numeric($rawModel)) {
                $x = (float)$rawModel;
            } else {
                // fallback: si hay modelos numéricos en el otro set, colocamos estos no-numéricos
                // justo después del minNumericModel para que sean visibles y comparables
                $fallbackCounter++;
                $x = $minNumericModel + 0.1 * $fallbackCounter; // pequeño desplazamiento
            }

            $y = (is_numeric($rawValor) ? (float)$rawValor : null);

            // solo agregamos puntos con valor numérico
            if ($y !== null) {
                $out[] = ['x' => $x, 'y' => $y, 'meta' => ['modelo' => (string)$rawModel]];
            }
        }
        return $out;
    };

    $clasificados = $mapSeries($rawClas, 0);
    $corregidos  = $mapSeries($rawCorr, 1000);

    // Helper: regresión exponencial y R^2 (modelo: y = a * exp(b * (x - x0)))
    $expRegression = function(array $points) {
        // points: [['x'=>, 'y'=>], ...]
        // Filtrar puntos válidos y y>0 (log requerirá y>0)
        $pts = array_values(array_filter($points, fn($p) => is_numeric($p['x']) && is_numeric($p['y']) && $p['y'] > 0));

        if (count($pts) < 2) {
            return null; // no hay suficientes puntos
        }

        $xs = array_column($pts, 'x');
        $ys = array_column($pts, 'y');

        $minX = min($xs);
        // Usaremos x' = x - minX para evitar exponentes gigantes
        $xprimes = array_map(fn($x) => $x - $minX, $xs);
        $lny = array_map(fn($y) => log($y), $ys);

        $n = count($xprimes);
        $sumX = array_sum($xprimes);
        $sumLnY = array_sum($lny);
        $sumX2 = array_sum(array_map(fn($x) => $x*$x, $xprimes));
        $sumXlnY = 0;
        for ($i=0;$i<$n;$i++) $sumXlnY += $xprimes[$i] * $lny[$i];

        $den = ($n * $sumX2 - $sumX * $sumX);
        if (abs($den) < 1e-12) return null;

        $b = ($n * $sumXlnY - $sumX * $sumLnY) / $den;
        $lnA = ($sumLnY - $b * $sumX) / $n;
        $a = exp($lnA);

        // Predicciones y R^2
        $yhat = [];
        for ($i=0;$i<$n;$i++) {
            $pred = $a * exp($b * $xprimes[$i]);
            $yhat[] = $pred;
        }
        $yMean = array_sum($ys) / $n;
        $SSE = 0; $SST = 0;
        for ($i=0;$i<$n;$i++){
            $SSE += pow($ys[$i] - $yhat[$i], 2);
            $SST += pow($ys[$i] - $yMean, 2);
        }
        $r2 = ($SST == 0) ? 1.0 : (1 - ($SSE / $SST));

        // generar curva suave entre minX y maxX usando x' y la fórmula
        $minXorig = min($xs);
        $maxXorig = max($xs);
        $curve = [];
        $steps = 200;
        for ($i=0;$i<=$steps;$i++){
            $x = $minXorig + ($maxXorig - $minXorig) * ($i / $steps);
            $xprime = $x - $minXorig;
            $y = $a * exp($b * $xprime);
            $curve[] = ['x' => $x, 'y' => $y];
        }

        // devolver parámetros y curva
        return [
            'a' => $a,
            'b' => $b,
            'r2' => $r2,
            'curve' => $curve,
            'minX' => $minXorig,
            'maxX' => $maxXorig,
        ];
    };

    $regClas = $expRegression($clasificados);
    $regCorr = $expRegression($corregidos);

    // Preparar datasets: puntos + curva (si existe)
    $datasets = [];

    // Clasificados: puntos
    $datasets[] = [
        'label' => 'Clasificados',
        'data' => $clasificados,
        'backgroundColor' => 'rgba(54,162,235,0.95)',
        'borderColor' => 'rgba(54,162,235,1)',
        'showLine' => false,
        'pointRadius' => 4,
    ];
    // Clasificados: curva si se calculó
    if ($regClas) {
        $datasets[] = [
            'label' => 'f(x) Clasificados (exp)  R²=' . number_format($regClas['r2'], 4),
            'data' => $regClas['curve'],
            'type' => 'line',
            'borderColor' => 'rgba(54,162,235,1)',
            'borderDash' => [6,4],
            'fill' => false,
            'pointRadius' => 0,
            'borderWidth' => 2,
        ];
    }

    // Corregidos: puntos
    $datasets[] = [
        'label' => 'Corregidos',
        'data' => $corregidos,
        'backgroundColor' => 'rgba(255,99,132,0.95)',
        'borderColor' => 'rgba(255,99,132,1)',
        'showLine' => false,
        'pointRadius' => 4,
    ];
    // Corregidos: curva si se calculó
    if ($regCorr) {
        $datasets[] = [
            'label' => 'f(x) Corregidos (exp)  R²=' . number_format($regCorr['r2'], 4),
            'data' => $regCorr['curve'],
            'type' => 'line',
            'borderColor' => 'rgba(255,99,132,1)',
            'borderDash' => [6,4],
            'fill' => false,
            'pointRadius' => 0,
            'borderWidth' => 2,
        ];
    }

    // Configuración Chart.js (no plugins externos)
    $chartConfig = [
        'type' => 'scatter',
        'data' => ['datasets' => $datasets],
        'options' => [
            'plugins' => [
                'legend' => ['display' => true],
                'title' => ['display' => true, 'text' => 'Clasificados y Corregidos con f(x) exponencial'],
            ],
            'scales' => [
                'x' => [
                    'type' => 'linear',
                    'title' => ['display' => true, 'text' => 'Modelo'],
                ],
                'y' => [
                    'title' => ['display' => true, 'text' => 'Valor'],
                ],
            ],
            'elements' => [
                'point' => [
                    'hoverRadius' => 6
                ]
            ],
            'responsive' => true,
        ],
    ];

    // Llamada a QuickChart
    $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->post('https://quickchart.io/chart', [
        'chart' => $chartConfig,
        'width' => 1000,
        'height' => 650,
        'format' => 'png',
        'version' => '4',
    ]);

    if ($response->successful()) {
        $image = $response->body();
        $filename = public_path("graficas/avaluo_{$avaluo->id}.png");
        if (!file_exists(dirname($filename))) mkdir(dirname($filename), 0777, true);
        file_put_contents($filename, $image);
        return "avaluo_{$avaluo->id}.png";
    }

    return null;
}

private function calcularExponencial(array $data, $xConsultar)
{
    // $data es un array de ['x' => modelo, 'y' => valor]

    $n = count($data);
    if ($n === 0) {
        return null;
    }

    // Transformación logarítmica: ln(y)
    $sumX = $sumY = $sumXY = $sumX2 = 0;

    foreach ($data as $p) {
        $x = $p['x'];
        $y = $p['y'];

        if ($y <= 0) {
            continue; // evitar log de 0 o negativo
        }

        $lnY = log($y);

        $sumX  += $x;
        $sumY  += $lnY;
        $sumXY += $x * $lnY;
        $sumX2 += $x * $x;
    }

    // Calcular coeficientes de regresión lineal en ln(y) = ln(a) + b * x
    /*$b = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $lnA = ($sumY - $b * $sumX) / $n;
    $a = exp($lnA);*/
    $denominador = ($n * $sumX2 - $sumX * $sumX);

        if ($denominador == 0) {
            // Evitar división por 0
            // Puedes decidir qué hacer: devolver 0, null, excepción, etc.
            $b = 0; 
        } else {
            $b = ($n * $sumXY - $sumX * $sumY) / $denominador;
        }

        if ($n == 0) {
            $lnA = 0; // Evitar división por 0
        } else {
            $lnA = ($sumY - $b * $sumX) / $n;
        }

        $a = exp($lnA);


    // Fórmula: y = a * e^(b * x)
    $yEstimado = $a * exp($b * $xConsultar);

    return [
        'a' => $a,
        'b' => $b,
        'formula' => "y = " . round($a, 2) . " * e^(" . round($b, 4) . " * x)",
        'valor_estimado' => $yEstimado,
    ];
}
}
