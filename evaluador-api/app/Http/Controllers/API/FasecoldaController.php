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
            $registros = FasecoldaValor::where('codigo_fasecolda', $codigo)->count();
            $ultimaActualizacion = FasecoldaValor::where('codigo_fasecolda', $codigo)->max('updated_at');

            return [
                'codigo_fasecolda' => $codigo,
                'registros' => $registros,
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
            'codigo_fasecolda' => 'required|string|max:50'
        ]);

        $nuevoCodigo = $request->codigo_fasecolda;

        if ($nuevoCodigo !== $codigo && FasecoldaValor::where('codigo_fasecolda', $nuevoCodigo)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una memoria con ese código'
            ], 422);
        }

        $actualizados = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->update(['codigo_fasecolda' => $nuevoCodigo]);

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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'codigo_fasecolda' => 'required|string|max:50'
        ]);
        
        try {
            // Eliminar valores existentes para este código
            FasecoldaValor::where('codigo_fasecolda', $request->codigo_fasecolda)->delete();
            
            // Importar archivo
            Excel::import(new FasecoldaImport($request->codigo_fasecolda), $request->file('file'));
            
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
                        'modelo' => $item->modelo,
                        'valor' => $item->valor
                    ];
                })->values();
            });
            
        return response()->json([
            'success' => true,
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
                'corregido' => $corregido
            ]
        ]);
    }
}