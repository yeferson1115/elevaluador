<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FasecoldaValor;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FasecoldaImport;

class FasecoldaController extends Controller
{

    public function index(Request $request)
    {
        $query = FasecoldaValor::query()->select('codigo_fasecolda')->distinct();

        if ($request->filled('codigo')) {
            $codigo = $request->get('codigo');
            $query->where('codigo_fasecolda', 'like', "%{$codigo}%");
        }

        $codigos = $query->orderBy('codigo_fasecolda')
            ->paginate((int) $request->get('per_page', 10));

        $codigos->getCollection()->transform(function ($item) {
            $codigo = $item->codigo_fasecolda;
            $registrosQuery = FasecoldaValor::where('codigo_fasecolda', $codigo);
            $registros = $registrosQuery->count();
            $ultimaActualizacion = $registrosQuery->max('updated_at');
            $pesoVacio = $registrosQuery->value('peso_vacio');

            return [
                'codigo_fasecolda' => $codigo,
                'registros' => $registros,
                'peso_vacio' => $pesoVacio,
                'updated_at' => $ultimaActualizacion,
            ];
        });

        return response()->json($codigos);
    }

    public function destroy($codigo)
    {
        $eliminados = FasecoldaValor::where('codigo_fasecolda', $codigo)->delete();

        if ($eliminados === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la memoria indicada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Memoria eliminada correctamente'
        ]);
    }

    public function update(Request $request, $codigo)
    {
        $request->validate([
            'codigo_fasecolda' => 'required|string|max:50',
            'peso_vacio' => 'nullable|numeric'
        ]);

        $nuevoCodigo = $request->codigo_fasecolda;

        if ($nuevoCodigo !== $codigo && FasecoldaValor::where('codigo_fasecolda', $nuevoCodigo)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una memoria con ese código'
            ], 422);
        }

        $datosActualizar = ['codigo_fasecolda' => $nuevoCodigo];

        if ($request->has('peso_vacio')) {
            $datosActualizar['peso_vacio'] = $request->peso_vacio;
        }

        $actualizados = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->update($datosActualizar);

        if ($actualizados === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la memoria indicada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Código de memoria actualizado correctamente'
        ]);
    }


    public function getRegistros($codigo)
    {
        $registros = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->orderBy('tipo')
            ->orderBy('modelo')
            ->get(['id', 'codigo_fasecolda', 'tipo', 'modelo', 'valor', 'peso_vacio', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $registros
        ]);
    }

    public function updateRegistro(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|in:clasificado,corregido',
            'modelo' => 'required|integer',
            'valor' => 'required|numeric',
            'peso_vacio' => 'nullable|numeric',
        ]);

        $registro = FasecoldaValor::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro indicado'
            ], 404);
        }

        $registro->update([
            'tipo' => $request->tipo,
            'modelo' => $request->modelo,
            'valor' => $request->valor,
            'peso_vacio' => $request->peso_vacio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
            'data' => $registro->fresh()
        ]);
    }

    public function destroyRegistro($id)
    {
        $registro = FasecoldaValor::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro indicado'
            ], 404);
        }

        $registro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'codigo_fasecolda' => 'required|string|max:50',
            'peso_vacio' => 'nullable|numeric'
        ]);
        
        try {
            // Eliminar valores existentes para este código
            FasecoldaValor::where('codigo_fasecolda', $request->codigo_fasecolda)->delete();
            
            // Importar archivo
            Excel::import(new FasecoldaImport($request->codigo_fasecolda, $request->peso_vacio), $request->file('file'));
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo importado correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getValores($codigo)
    {
        $valores = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->get()
            ->groupBy('tipo')
            ->map(function($items) {
                return $items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'tipo' => $item->tipo,
                        'modelo' => $item->modelo,
                        'valor' => $item->valor,
                        'peso_vacio' => $item->peso_vacio
                    ];
                })->values();
            });
            
        return response()->json([
            'success' => true,
            'peso_vacio' => FasecoldaValor::where('codigo_fasecolda', $codigo)->value('peso_vacio'),
            'data' => $valores
        ]);
    }
    
    public function buscarPorModelo($codigo, $modelo)
    {
        $clasificado = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->where('tipo', 'clasificado')
            ->where('modelo', $modelo)
            ->first();
            
        $corregido = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->where('tipo', 'corregido')
            ->where('modelo', $modelo)
            ->first();
            
        return response()->json([
            'success' => true,
            'data' => [
                'clasificado' => $clasificado,
                'corregido' => $corregido,
                'peso_vacio' => $clasificado?->peso_vacio ?? $corregido?->peso_vacio
            ]
        ]);
    }
}