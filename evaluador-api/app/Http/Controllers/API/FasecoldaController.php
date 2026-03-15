<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FasecoldaValor;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FasecoldaImport;

class FasecoldaController extends Controller
{
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