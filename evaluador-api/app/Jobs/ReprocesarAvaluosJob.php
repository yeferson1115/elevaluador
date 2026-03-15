<?php

namespace App\Jobs;

use App\Models\Avaluo;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReprocesarAvaluosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Número de avalúos a procesar
     */
    protected int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 50)
    {
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando reprocesamiento de avalúos con archivos PDF', [
            'limit' => $this->limit
        ]);

        // Obtener avalúos que tienen archivo y no están vacíos
        $avaluos = Avaluo::query()
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->with([
                'ingreso', 
                'clasificados', 
                'corregidos', 
                'limitaciones',
                'user' => fn($query) => $query->withTrashed() // Si usas soft deletes
            ])
            ->orderBy('id', 'desc') // Procesar los más recientes primero
            ->limit($this->limit)
            ->get();

        Log::info("Encontrados {$avaluos->count()} avalúos para reprocesar");

        $procesados = 0;
        $errores = 0;

        foreach ($avaluos as $avaluo) {
            try {
                $this->reprocesarAvaluo($avaluo);
                $procesados++;
                
                // Pequeña pausa para no sobrecargar el sistema
                if ($procesados % 10 === 0) {
                    sleep(1);
                }
                
            } catch (\Throwable $e) {
                $errores++;
                Log::error("Error reprocesando avalúo ID {$avaluo->id}", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        Log::info('Reprocesamiento completado', [
            'total' => $avaluos->count(),
            'procesados' => $procesados,
            'errores' => $errores
        ]);
    }

    /**
     * Método principal para reprocesar un avalúo
     */
    private function reprocesarAvaluo(Avaluo $avaluo): void
    {
        // Cargar relaciones necesarias si no están cargadas
        if (!$avaluo->relationLoaded('ingreso')) {
            $avaluo->load('ingreso');
        }
        
        if (!$avaluo->relationLoaded('corregidos')) {
            $avaluo->load('corregidos');
        }

        $ingreso = $avaluo->ingreso;
        
        if (!$ingreso) {
            throw new \Exception("No se encontró ingreso para el avalúo ID: {$avaluo->id}");
        }

        // Cargar imágenes del ingreso si es necesario
        if (!$ingreso->relationLoaded('images')) {
            $ingreso->load('images');
        }

        // Usuario asociado
        $user = $avaluo->user ?? User::first();
        
        if (!$user) {
            $user = new User();
            $user->name = 'Sistema';
            $user->email = 'sistema@example.com';
        }

        // Variables para la generación del PDF
        $graficaPath = null;
        $resultado = null;
        
        // Generar gráfica si es necesario
        if (in_array($avaluo->tipo, ['comercial', 'jans'])) {
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            
            if ($avaluo->corregidos->isNotEmpty()) {
                $corregidos = $avaluo->corregidos->map(function($c) {
                    return [
                        'x' => (int) $c->modelo,
                        'y' => (float) $c->valor
                    ];
                })->toArray();

                $modeloConsultar = (int) ($ingreso->modelo ?? 0);
                
                if ($modeloConsultar > 0) {
                    $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);
                }
            }
        }

        // Determinar qué vista usar para el PDF
        $pdf = $this->generarPdf($ingreso, $avaluo, $graficaPath, $resultado, $user);

        // Generar nombre de archivo
        $nombreArchivo = $this->generarNombreArchivo($ingreso, $avaluo);

        // Guardar archivo
        $this->guardarPdf($pdf, $nombreArchivo);

        // Actualizar registro del avalúo
        $avaluo->file = $nombreArchivo;
        $avaluo->save();

        // Actualizar consecutivo si es necesario
        $this->actualizarConsecutivo($avaluo);
    }

    /**
     * Generar el PDF según el tipo
     */
    private function generarPdf($ingreso, $avaluo, $graficaPath, $resultado, $user)
    {
        if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
            return Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        } 
        
        if (in_array($avaluo->tipo, ['comercial', 'jans'])) {
            return Pdf::loadView('pdf.avaluojans', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        }
        
        return Pdf::loadView('pdf.avaluo', compact('ingreso', 'avaluo', 'user'));
    }

    /**
     * Generar nombre de archivo
     */
    private function generarNombreArchivo($ingreso, $avaluo): string
    {
        if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
            return ($ingreso->placa ?? 'sin-placa') . '-' . 
                   ($avaluo->inicial ?? '') . 
                   ($avaluo->consecutivo ?? '') . '.pdf';
        }
        
        return 'avaluo_' . $avaluo->id . '_' . now()->format('Ymd_His') . '.pdf';
    }

    /**
     * Guardar PDF en el sistema de archivos
     */
    private function guardarPdf($pdf, string $nombreArchivo): void
    {
        $ruta = public_path('documentos/' . $nombreArchivo);
        $directorio = dirname($ruta);
        
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        file_put_contents($ruta, $pdf->output());
        
        Log::info("PDF guardado: {$ruta}");
    }

    /**
     * Actualizar consecutivo e inicial si es necesario
     */
    private function actualizarConsecutivo(Avaluo $avaluo): void
    {
        if ($avaluo->evaluador && empty($avaluo->consecutivo)) {
            $ultimoAvaluo = Avaluo::where('evaluador', $avaluo->evaluador)
                ->where('id', '!=', $avaluo->id)
                ->orderBy('consecutivo', 'desc')
                ->first();

            // Generar iniciales
            $inicial = collect(explode(' ', trim($avaluo->evaluador)))
                ->map(fn($palabra) => strtoupper(substr($palabra, 0, 1)))
                ->implode('');

            $avaluo->consecutivo = $ultimoAvaluo ? $ultimoAvaluo->consecutivo + 1 : 1;
            $avaluo->inicial = $inicial;
            $avaluo->save();
        }
    }

    /**
     * Métodos auxiliares (copiados del controlador)
     */
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

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job ReprocesarAvaluosJob falló: ' . $exception->getMessage());
    }
}