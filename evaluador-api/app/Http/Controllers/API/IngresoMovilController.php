<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\IngresoMovilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngresoMovilController extends Controller
{
    public function __construct(private readonly IngresoMovilService $ingresoMovilService)
    {
    }

    public function guardar(Request $request): JsonResponse
    {
        $resultado = $this->ingresoMovilService->crearOEditarDesdeMovil($request);

        return response()->json([
            'message' => $resultado['created']
                ? 'Ingreso móvil creado correctamente.'
                : 'Ingreso móvil actualizado correctamente.',
            'data' => $resultado,
        ], $resultado['created'] ? 201 : 200);
    }
}
