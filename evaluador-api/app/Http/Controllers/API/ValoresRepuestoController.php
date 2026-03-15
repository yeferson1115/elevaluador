<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ValoresRepuesto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ValoresRepuestoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
{
    try {
        $query = ValoresRepuesto::query();

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', 'LIKE', '%' . $request->tipo . '%');
        }

        // Filtro por cilindraje (busca dentro del rango from - to)
        if ($request->filled('cilindraje')) {
            $cilindraje = $request->cilindraje;

            $query->where('cilindraje_from', '<=', $cilindraje)
                  ->where('cilindraje_to', '>=', $cilindraje);
        }

        if ($request->has('especial')) {
            $especial = filter_var($request->input('especial'), FILTER_VALIDATE_BOOLEAN);
            $query->where('especial', $especial ? 1 : 0);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);

        $valoresRepuestos = $query->orderBy('tipo')
                                  ->orderBy('cilindraje_from')
                                  ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Lista de valores de repuestos obtenida exitosamente',
            'data' => $valoresRepuestos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener los valores de repuestos: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cilindraje_from' => 'required|string|max:10',
                'cilindraje_to' => 'required|string|max:10',
                'tipo' => 'required|string|max:50',
                'especial' => 'nullable|boolean',
                'llantas' => 'nullable|numeric|min:0',
                'tapiceria' => 'nullable|numeric|min:0',
                'soat' => 'nullable|numeric|min:0',
                'rtm' => 'nullable|numeric|min:0',
                'kit_arrastre' => 'nullable|numeric|min:0',
                'motor_mantenimiento' => 'nullable|numeric|min:0',
                'pintura' => 'nullable|numeric|min:0',
                'latoneria' => 'nullable|numeric|min:0',
                'chasis' => 'nullable|numeric|min:0',
                'frenos' => 'nullable|numeric|min:0',
                'bateria' => 'nullable|numeric|min:0',
                'tanque_combustible' => 'nullable|numeric|min:0',
                'llave' => 'nullable|numeric|min:0',
                'sis_electrico' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si ya existe un registro con el mismo tipo y cilindrage
            $exists = ValoresRepuesto::where('tipo', $request->tipo)
                                     ->where('cilindrage', $request->cilindrage)
                                     ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un registro con el mismo tipo y cilindraje',
                    'data' => null
                ], 409);
            }

            $data = $request->all();
            $data['especial'] = $request->boolean('especial');

            $valoresRepuesto = ValoresRepuesto::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Valor de repuesto creado exitosamente',
                'data' => $valoresRepuesto
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el valor de repuesto: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $valoresRepuesto = ValoresRepuesto::find($id);

            if (!$valoresRepuesto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor de repuesto no encontrado',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Valor de repuesto encontrado',
                'data' => $valoresRepuesto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el valor de repuesto: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $valoresRepuesto = ValoresRepuesto::find($id);

            if (!$valoresRepuesto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor de repuesto no encontrado',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'cilindrage' => 'sometimes|required|string|max:10',
                'tipo' => 'sometimes|required|string|max:50',
                'especial' => 'sometimes|boolean',
                'llantas' => 'nullable|numeric|min:0',
                'tapiceria' => 'nullable|numeric|min:0',
                'soat' => 'nullable|numeric|min:0',
                'rtm' => 'nullable|numeric|min:0',
                'kit_arrastre' => 'nullable|numeric|min:0',
                'motor_mantenimiento' => 'nullable|numeric|min:0',
                'pintura' => 'nullable|numeric|min:0',
                'latoneria' => 'nullable|numeric|min:0',
                'chasis' => 'nullable|numeric|min:0',
                'frenos' => 'nullable|numeric|min:0',
                'bateria' => 'nullable|numeric|min:0',
                'tanque_combustible' => 'nullable|numeric|min:0',
                'llave' => 'nullable|numeric|min:0',
                'sis_electrico' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar duplicados si se está cambiando tipo o cilindrage
            if (($request->has('tipo') && $request->tipo != $valoresRepuesto->tipo) || 
                ($request->has('cilindrage') && $request->cilindrage != $valoresRepuesto->cilindrage)) {
                
                $exists = ValoresRepuesto::where('tipo', $request->tipo ?? $valoresRepuesto->tipo)
                                         ->where('cilindrage', $request->cilindrage ?? $valoresRepuesto->cilindrage)
                                         ->where('id', '!=', $id)
                                         ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe otro registro con el mismo tipo y cilindraje',
                        'data' => null
                    ], 409);
                }
            }

            $data = $request->all();
            if ($request->has('especial')) {
                $data['especial'] = $request->boolean('especial');
            }

            $valoresRepuesto->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Valor de repuesto actualizado exitosamente',
                'data' => $valoresRepuesto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el valor de repuesto: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $valoresRepuesto = ValoresRepuesto::find($id);

            if (!$valoresRepuesto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor de repuesto no encontrado',
                    'data' => null
                ], 404);
            }

            $valoresRepuesto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Valor de repuesto eliminado exitosamente',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el valor de repuesto: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Buscar valor de repuesto por clase y cilindraje con rangos específicos
     * AHORA ES DINÁMICO: usa los valores reales de la base de datos
     */
public function buscarPorCilindraje(Request $request): JsonResponse
{
    try {
        $request->validate([
            'clase' => 'required|string',
            'cilindraje' => 'required|integer|min:1|max:9999',
            'especial' => 'nullable|boolean'
        ]);

        $clase = strtoupper(trim($request->input('clase')));
        $cilindrajeBuscado = (int) $request->input('cilindraje');
        $especial = filter_var($request->input('especial', false), FILTER_VALIDATE_BOOLEAN);

        $registro = ValoresRepuesto::where('tipo', $clase)
            ->where('especial', $especial ? 1 : 0)
            ->where(function ($query) use ($cilindrajeBuscado) {

                // Caso 1: Rango normal
                $query->where(function ($q) use ($cilindrajeBuscado) {
                    $q->whereNotNull('cilindraje_from')
                      ->where('cilindraje_to', '<=', $cilindrajeBuscado)
                      ->where('cilindraje_from', '>=', $cilindrajeBuscado);
                })

                // Caso 2: Sin límite superior (from NULL)
                ->orWhere(function ($q) use ($cilindrajeBuscado) {
                    $q->whereNull('cilindraje_from')
                      ->where('cilindraje_to', '<=', $cilindrajeBuscado);
                });

            })
            ->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un valor para el cilindraje especificado',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Valores de repuesto encontrados',
            'data' => $registro
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

    /**
     * Encuentra el cilindraje disponible más cercano al buscado
     * Utiliza lógica de "redondeo" hacia el valor predefinido más próximo
     */
    private function encontrarCilindrajeMasCercano(int $buscado, array $disponibles): string
    {
        // Ordenar los disponibles (ya deberían estar ordenados)
        sort($disponibles);
        
        $cercano = null;
        $diferenciaMinima = PHP_INT_MAX;
        
        foreach ($disponibles as $valor) {
            $diferencia = abs($valor - $buscado);
            
            if ($diferencia < $diferenciaMinima) {
                $diferenciaMinima = $diferencia;
                $cercano = $valor;
            } elseif ($diferencia == $diferenciaMinima) {
                // En caso de empate (ej: buscar 102 con disponibles 100 y 115),
                // elegir el menor si está más cerca o reglas de negocio específicas
                // Por ahora, mantenemos el primero encontrado
            }
        }
        
        return (string) $cercano;
    }

    /**
     * Versión alternativa con lógica de "techo" (siempre redondear hacia arriba)
     * o "piso" (siempre redondear hacia abajo) - descomenta la que prefieras
     */
    /*
    private function encontrarCilindrajeMasCercano(int $buscado, array $disponibles): string
    {
        sort($disponibles);
        
        // Opción 1: Redondear hacia arriba (si buscas 102, te da 115)
        foreach ($disponibles as $valor) {
            if ($valor >= $buscado) {
                return (string) $valor;
            }
        }
        
        // Opción 2: Redondear hacia abajo (si buscas 102, te da 100)
        $anterior = end($disponibles);
        foreach ($disponibles as $valor) {
            if ($valor > $buscado) {
                return (string) $anterior;
            }
            $anterior = $valor;
        }
        
        return (string) end($disponibles);
    }
    */

    /**
     * Buscar por rango de cilindraje
     */
    public function buscarPorRango(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clase' => 'required|string',
                'min_cilindraje' => 'required|integer|min:1',
                'max_cilindraje' => 'required|integer|min:1|gte:min_cilindraje'
            ]);

            $clase = strtoupper(trim($request->clase));
            $minCilindraje = (int) $request->min_cilindraje;
            $maxCilindraje = (int) $request->max_cilindraje;

            $registros = ValoresRepuesto::where('tipo', $clase)
                ->whereRaw('CAST(cilindrage AS UNSIGNED) BETWEEN ? AND ?', [$minCilindraje, $maxCilindraje])
                ->orderByRaw('CAST(cilindrage AS UNSIGNED)')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Registros encontrados en el rango',
                'data' => $registros,
                'total' => $registros->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener todos los valores para una clase específica
     */
    public function porClase($clase): JsonResponse
    {
        try {
            $clase = strtoupper(trim($clase));
            
            $registros = ValoresRepuesto::where('tipo', $clase)
                ->orderByRaw('CAST(cilindrage AS UNSIGNED)')
                ->get();

            if ($registros->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros para la clase: ' . $clase,
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registros encontrados',
                'data' => $registros,
                'total' => $registros->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener tipos únicos para filtros
     */
    public function getTipos(): JsonResponse
    {
        try {
            $tipos = ValoresRepuesto::select('tipo')
                ->distinct()
                ->orderBy('tipo')
                ->pluck('tipo');

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener todos los cilindrajes disponibles para una clase
     */
    public function getCilindrajesPorClase($clase): JsonResponse
    {
        try {
            $clase = strtoupper(trim($clase));
            
            $cilindrajes = ValoresRepuesto::where('tipo', $clase)
                ->orderByRaw('CAST(cilindrage AS UNSIGNED)')
                ->pluck('cilindrage');

            return response()->json([
                'success' => true,
                'data' => $cilindrajes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cilindrajes: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}