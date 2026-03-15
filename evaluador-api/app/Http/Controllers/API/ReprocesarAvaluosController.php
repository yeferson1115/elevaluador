<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Avaluo;
use App\Models\Ingreso;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReprocesarSimpleController extends Controller
{
    /**
     * URL: /reprocesar/avaluos
     * Reprocesa automáticamente todos los avalúos con archivos
     */
    public function reprocesarTodo(Request $request)
    {
        // Contar avalúos a procesar
        $total = Avaluo::whereNotNull('file')
            ->where('file', '!=', '')
            ->count();
            
        Log::info('=== INICIANDO REPROCESAMIENTO AUTOMÁTICO ===', [
            'total_avaluos' => $total,
            'url' => $request->fullUrl(),
            'ip' => $request->ip()
        ]);
        
        if ($total === 0) {
            return response("<h1>✅ No hay avalúos para reprocesar</h1>");
        }
        
        // Iniciar procesamiento
        $resultado = $this->procesarAvaluos();
        
        // Respuesta HTML simple
        return response("
            <html>
            <head><title>Reprocesamiento Completado</title></head>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <h1>✅ Reprocesamiento Completado</h1>
                <h3>Resultados:</h3>
                <ul>
                    <li><strong>Total encontrados:</strong> {$resultado['total']}</li>
                    <li><strong>Procesados exitosamente:</strong> {$resultado['exitosos']}</li>
                    <li><strong>Con errores:</strong> {$resultado['errores']}</li>
                    <li><strong>Tiempo:</strong> {$resultado['tiempo']} segundos</li>
                </ul>
                <h4>Detalles de errores:</h4>
                <pre>{$resultado['log_errores']}</pre>
                <p><a href='/'>Volver al inicio</a></p>
            </body>
            </html>
        ");
    }
    
    /**
     * URL: /reprocesar/avaluos/limitado
     * Reprocesa solo 5 avalúos (para pruebas)
     */
    public function reprocesarLimitado(Request $request)
    {
        Log::info('Reprocesamiento limitado iniciado');
        
        $resultado = $this->procesarAvaluos(5);
        
        return response("
            <h2>Reprocesamiento Limitado (5 avalúos)</h2>
            <p><strong>Procesados:</strong> {$resultado['exitosos']}/{$resultado['total']}</p>
            <p><strong>Errores:</strong> {$resultado['errores']}</p>
            <p><a href='/reprocesar/avaluos'>Reprocesar TODOS</a></p>
        ");
    }
    
    /**
     * URL: /reprocesar/avaluos/{id}
     * Reprocesa un avalúo específico por ID
     */
    public function reprocesarUno($id, Request $request)
    {
        Log::info("Reprocesando avalúo individual ID: {$id}");
        
        $avaluo = Avaluo::with(['ingreso', 'corregidos', 'clasificados'])
            ->find($id);
            
        if (!$avaluo) {
            return response("<h2>❌ Avalúo ID {$id} no encontrado</h2>");
        }
        
        if (empty($avaluo->file)) {
            return response("<h2>⚠️ Avalúo ID {$id} no tiene archivo para reprocesar</h2>");
        }
        
        try {
            $this->reprocesarAvaluoIndividual($avaluo);
            return response("
                <h2>✅ Avalúo ID {$id} reprocesado exitosamente</h2>
                <p><strong>Nuevo archivo:</strong> {$avaluo->file}</p>
                <p><strong>Placa:</strong> " . ($avaluo->ingreso->placa ?? 'N/A') . "</p>
                <p><a href='/reprocesar/avaluos'>Volver</a></p>
            ");
        } catch (\Exception $e) {
            return response("
                <h2>❌ Error reprocesando avalúo ID {$id}</h2>
                <p><strong>Error:</strong> {$e->getMessage()}</p>
                <p><a href='/reprocesar/avaluos'>Volver</a></p>
            ");
        }
    }
    
    /**
     * Método principal para procesar avalúos
     */
    private function procesarAvaluos($limit = null)
    {
        $inicio = microtime(true);
        
        $query = Avaluo::whereNotNull('file')
            ->where('file', '!=', '')
            ->with(['ingreso', 'corregidos', 'clasificados']);
            
        if ($limit) {
            $query->limit($limit);
        }
            
        $avaluos = $query->get();
        $total = $avaluos->count();
        
        $exitosos = 0;
        $errores = 0;
        $logErrores = "";
        
        foreach ($avaluos as $avaluo) {
            try {
                $this->reprocesarAvaluoIndividual($avaluo);
                $exitosos++;
                Log::info("✅ Avalúo {$avaluo->id} reprocesado: {$avaluo->file}");
            } catch (\Exception $e) {
                $errores++;
                $errorMsg = "❌ Error avalúo {$avaluo->id}: " . $e->getMessage();
                $logErrores .= $errorMsg . "\n";
                Log::error($errorMsg);
            }
        }
        
        $tiempo = round(microtime(true) - $inicio, 2);
        
        Log::info('=== REPROCESAMIENTO COMPLETADO ===', [
            'total' => $total,
            'exitosos' => $exitosos,
            'errores' => $errores,
            'tiempo_segundos' => $tiempo
        ]);
        
        return [
            'total' => $total,
            'exitosos' => $exitosos,
            'errores' => $errores,
            'tiempo' => $tiempo,
            'log_errores' => $logErrores
        ];
    }
    
    /**
     * Lógica para reprocesar un avalúo individual
     */
    private function reprocesarAvaluoIndividual(Avaluo $avaluo)
    {
        $ingreso = $avaluo->ingreso;
        
        if (!$ingreso) {
            throw new \Exception("No tiene ingreso asociado");
        }
        
        // Obtener usuario (el primero disponible)
        $user = User::first();
        if (!$user) {
            $user = new User();
            $user->name = 'Sistema';
        }
        
        // Determinar qué vista usar
        if ($avaluo->formato == 'Sec. Movilidad Bogotá' || 
            ($ingreso->tiposervicio === "Sec Bogota")) {
            
            // Para Sec Bogotá
            $pdf = Pdf::loadView('pdf.avaluosecbogota', [
                'ingreso' => $ingreso,
                'avaluo' => $avaluo,
                'graficaPath' => null,
                'resultado' => null,
                'user' => $user
            ]);
            
            // Nombre para Sec Bogotá
            $nombreArchivo = ($ingreso->placa ?? 'sin-placa') . '-' . 
                           ($avaluo->inicial ?? 'X') . 
                           ($avaluo->consecutivo ?? '001') . '.pdf';
            
        } elseif (in_array($avaluo->tipo, ['comercial', 'jans'])) {
            
            // Para tipo comercial/jans con gráfica
            $graficaPath = null; // Puedes generar la gráfica si quieres
            
            // Calcular exponencial si hay corregidos
            $resultado = null;
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
            
            $pdf = Pdf::loadView('pdf.avaluojans', [
                'ingreso' => $ingreso,
                'avaluo' => $avaluo,
                'graficaPath' => $graficaPath,
                'resultado' => $resultado,
                'user' => $user
            ]);
            
            $nombreArchivo = 'avaluo_' . $avaluo->id . '_' . date('Ymd_His') . '.pdf';
            
        } else {
            
            // Para otros tipos
            $pdf = Pdf::loadView('pdf.avaluo', [
                'ingreso' => $ingreso,
                'avaluo' => $avaluo,
                'user' => $user
            ]);
            
            $nombreArchivo = 'avaluo_' . $avaluo->id . '_' . date('Ymd_His') . '.pdf';
        }
        
        // Ruta para guardar
        $ruta = public_path('documentos/' . $nombreArchivo);
        
        // Crear directorio si no existe
        $directorio = dirname($ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Guardar PDF
        file_put_contents($ruta, $pdf->output());
        
        // Actualizar registro en BD
        $avaluo->file = $nombreArchivo;
        $avaluo->save();
        
        // Si no tiene consecutivo, generarlo
        if ($avaluo->evaluador && empty($avaluo->consecutivo)) {
            $this->generarConsecutivo($avaluo);
        }
        
        return true;
    }
    
    /**
     * Generar consecutivo automático
     */
    private function generarConsecutivo(Avaluo $avaluo)
    {
        $ultimo = Avaluo::where('evaluador', $avaluo->evaluador)
            ->where('id', '!=', $avaluo->id)
            ->orderBy('consecutivo', 'desc')
            ->first();
            
        $consecutivo = $ultimo ? $ultimo->consecutivo + 1 : 1;
        
        // Generar iniciales
        $inicial = '';
        $palabras = explode(' ', trim($avaluo->evaluador));
        foreach ($palabras as $palabra) {
            $inicial .= strtoupper(substr($palabra, 0, 1));
        }
        
        $avaluo->consecutivo = $consecutivo;
        $avaluo->inicial = $inicial;
        $avaluo->save();
    }
    
    /**
     * Calcular fórmula exponencial (copiada de tu controlador)
     */
    private function calcularExponencial(array $data, $xConsultar)
    {
        $n = count($data);
        if ($n === 0) {
            return null;
        }
        
        $sumX = $sumY = $sumXY = $sumX2 = 0;
        
        foreach ($data as $p) {
            $x = $p['x'];
            $y = $p['y'];
            
            if ($y <= 0) continue;
            
            $lnY = log($y);
            $sumX += $x;
            $sumY += $lnY;
            $sumXY += $x * $lnY;
            $sumX2 += $x * $x;
        }
        
        $denominador = ($n * $sumX2 - $sumX * $sumX);
        
        if ($denominador == 0) {
            $b = 0;
        } else {
            $b = ($n * $sumXY - $sumX * $sumY) / $denominador;
        }
        
        if ($n == 0) {
            $lnA = 0;
        } else {
            $lnA = ($sumY - $b * $sumX) / $n;
        }
        
        $a = exp($lnA);
        $yEstimado = $a * exp($b * $xConsultar);
        
        return [
            'a' => $a,
            'b' => $b,
            'formula' => "y = " . round($a, 2) . " * e^(" . round($b, 4) . " * x)",
            'valor_estimado' => $yEstimado,
        ];
    }
    
    /**
     * URL: /reprocesar/estado
     * Ver estado de avalúos
     */
    public function verEstado()
    {
        $total = Avaluo::whereNotNull('file')
            ->where('file', '!=', '')
            ->count();
            
        $avaluos = Avaluo::whereNotNull('file')
            ->where('file', '!=', '')
            ->with('ingreso')
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get(['id', 'file', 'ingreso_id', 'updated_at']);
            
        $html = "<h2>📊 Estado de Avalúos para Reprocesar</h2>";
        $html .= "<p><strong>Total pendientes:</strong> {$total}</p>";
        
        if ($avaluos->isNotEmpty()) {
            $html .= "<h3>Últimos 20 avalúos:</h3>";
            $html .= "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
            $html .= "<tr><th>ID</th><th>Archivo Actual</th><th>Placa</th><th>Última Actualización</th><th>Acción</th></tr>";
            
            foreach ($avaluos as $avaluo) {
                $placa = $avaluo->ingreso->placa ?? 'N/A';
                $fecha = $avaluo->updated_at->format('Y-m-d H:i');
                $html .= "<tr>";
                $html .= "<td>{$avaluo->id}</td>";
                $html .= "<td>{$avaluo->file}</td>";
                $html .= "<td>{$placa}</td>";
                $html .= "<td>{$fecha}</td>";
                $html .= "<td><a href='/reprocesar/avaluos/{$avaluo->id}'>Reprocesar</a></td>";
                $html .= "</tr>";
            }
            
            $html .= "</table>";
        }
        
        $html .= "<br>";
        $html .= "<p><a href='/reprocesar/avaluos' style='color: red; font-weight: bold;'>⚠️ REPROCESAR TODOS LOS AVALÚOS</a></p>";
        $html .= "<p><a href='/reprocesar/avaluos/limitado'>Reprocesar 5 avalúos (prueba)</a></p>";
        
        return response($html);
    }
}