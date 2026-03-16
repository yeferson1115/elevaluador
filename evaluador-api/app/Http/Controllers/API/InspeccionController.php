<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Models\Inspeccion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\InspeccionMecanica;
use App\Models\InspeccionTapiceria;
use App\Models\InspeccionFuncionamiento;
use App\Models\InspeccionLuces;
use App\Models\InspeccionExterior;
use App\Models\InspeccionIndicadores;
use App\Models\InspeccionParteBaja;
use App\Models\InspeccionAccesorios;
use App\Models\InspeccionRevisionVisualPuntoLiviano;
use App\Models\InspeccionRevisionVisualPuntoMoto;

use Barryvdh\DomPDF\Facade\Pdf;

class InspeccionController extends Controller
{
    private function tipoVehiculoUsaRevisionPuntoLiviano(?string $tipoVehiculo): bool
    {
        return $tipoVehiculo === 'Liviano';
    }

    private function tipoVehiculoUsaRevisionPuntoMoto(?string $tipoVehiculo): bool
    {
        return in_array($tipoVehiculo, ['Motocileta', 'Motocicleta'], true);
    }

    private function guardarRevisionVisualPuntoLiviano(Inspeccion $inspeccion, array $data): void
    {
        if (!array_key_exists('inspeccion_revision_visual_punto_liviano', $data) || !is_array($data['inspeccion_revision_visual_punto_liviano'])) {
            return;
        }

        if (!$this->tipoVehiculoUsaRevisionPuntoLiviano($data['tipo_vehiculo'] ?? null)) {
            return;
        }

        $inspeccion->inspeccionRevisionVisualPuntoLiviano()->updateOrCreate(
            ['inspeccion_id' => $inspeccion->id],
            $data['inspeccion_revision_visual_punto_liviano']
        );
    }

    private function guardarRevisionVisualPuntoMoto(Inspeccion $inspeccion, array $data): void
    {
        if (!array_key_exists('inspeccion_revision_visual_punto_moto', $data) || !is_array($data['inspeccion_revision_visual_punto_moto'])) {
            return;
        }

        if (!$this->tipoVehiculoUsaRevisionPuntoMoto($data['tipo_vehiculo'] ?? null)) {
            return;
        }

        $inspeccion->inspeccionRevisionVisualPuntoMoto()->updateOrCreate(
            ['inspeccion_id' => $inspeccion->id],
            $data['inspeccion_revision_visual_punto_moto']
        );
    }

    // Obtener listado paginado con búsqueda
    public function index(Request $request)
    {
        $query = Ingreso::query();

        // Filtro principal: tipo de servicio
        if ($request->has('tipo') && !empty($request->tipo)) {
            //$query->where('tiposervicio', $request->tipo);
            $query->whereIn('tiposervicio', [$request->tipo,'Avaluo e Inspección']);
        }

        // Filtro secundario: búsqueda por texto
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'like', "%{$search}%")
                ->orWhere('solicitante', 'like', "%{$search}%")
                ->orWhere('documento_solicitante', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }




    // Obtener un avalúo específico
public function show($id)
{
    $ingreso = Ingreso::with([
    'inspeccion.inspeccionExterior',
    'inspeccion.inspeccionFuncionamiento',
    'inspeccion.inspeccionIndicadores',
    'inspeccion.inspeccionLuces',
    'inspeccion.inspeccionMecanica',
    'inspeccion.inspeccionTapiceria',
    'inspeccion.inspeccionAccesorios',
    'inspeccion.inspeccionParteBaja',
    'inspeccion.inspeccionRevisionVisualPuntoLiviano',
    'inspeccion.inspeccionRevisionVisualPuntoMoto'
])
->find($id);

    if (!$ingreso) {
        return response()->json(['message' => 'Ingreso no encontrado'], Response::HTTP_NOT_FOUND);
    }

    if (!$ingreso->inspeccion) {
        $inspeccion = new \App\Models\Inspeccion();
        $inspeccion->fill(array_fill_keys($inspeccion->getFillable(), null));

        $inspeccion->setRelation('inspeccionExterior', new \App\Models\InspeccionExterior(array_fill_keys((new \App\Models\InspeccionExterior)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionFuncionamiento', new \App\Models\InspeccionFuncionamiento(array_fill_keys((new \App\Models\InspeccionFuncionamiento)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionIndicadores', new \App\Models\InspeccionIndicadores(array_fill_keys((new \App\Models\InspeccionIndicadores)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionLuces', new \App\Models\InspeccionLuces(array_fill_keys((new \App\Models\InspeccionLuces)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionMecanica', new \App\Models\InspeccionMecanica(array_fill_keys((new \App\Models\InspeccionMecanica)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionTapiceria', new \App\Models\InspeccionTapiceria(array_fill_keys((new \App\Models\InspeccionTapiceria)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionAccesorios', new \App\Models\InspeccionAccesorios(array_fill_keys((new \App\Models\InspeccionAccesorios)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionParteBaja', new \App\Models\InspeccionParteBaja(array_fill_keys((new \App\Models\InspeccionParteBaja)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionVisual', new \App\Models\InspeccionVisual(array_fill_keys((new \App\Models\InspeccionVisual)->getFillable(), null)));
        $inspeccion->setRelation('inspeccionRevisionVisual', new \App\Models\InspeccionRevisionVisual(array_fill_keys((new \App\Models\InspeccionRevisionVisual)->getFillable(), null)));
         $inspeccion->setRelation('inspeccionRevisionVisualPuntoLiviano', new \App\Models\InspeccionRevisionVisualPuntoLiviano(array_fill_keys((new \App\Models\InspeccionRevisionVisualPuntoLiviano)->getFillable(), null)));
         $inspeccion->setRelation('inspeccionRevisionVisualPuntoMoto', new \App\Models\InspeccionRevisionVisualPuntoMoto(array_fill_keys((new \App\Models\InspeccionRevisionVisualPuntoMoto)->getFillable(), null)));

        $ingreso->setRelation('inspeccion', $inspeccion);
    } else {
        $inspeccion = $ingreso->inspeccion;

        if (!$inspeccion->inspeccionExterior)
            $inspeccion->setRelation('inspeccionExterior', new \App\Models\InspeccionExterior(array_fill_keys((new \App\Models\InspeccionExterior)->getFillable(), null)));

        if (!$inspeccion->inspeccionFuncionamiento)
            $inspeccion->setRelation('inspeccionFuncionamiento', new \App\Models\InspeccionFuncionamiento(array_fill_keys((new \App\Models\InspeccionFuncionamiento)->getFillable(), null)));

        if (!$inspeccion->inspeccionIndicadores)
            $inspeccion->setRelation('inspeccionIndicadores', new \App\Models\InspeccionIndicadores(array_fill_keys((new \App\Models\InspeccionIndicadores)->getFillable(), null)));

        if (!$inspeccion->inspeccionLuces)
            $inspeccion->setRelation('inspeccionLuces', new \App\Models\InspeccionLuces(array_fill_keys((new \App\Models\InspeccionLuces)->getFillable(), null)));

        if (!$inspeccion->inspeccionMecanica)
            $inspeccion->setRelation('inspeccionMecanica', new \App\Models\InspeccionMecanica(array_fill_keys((new \App\Models\InspeccionMecanica)->getFillable(), null)));

        if (!$inspeccion->inspeccionTapiceria)
            $inspeccion->setRelation('inspeccionTapiceria', new \App\Models\InspeccionTapiceria(array_fill_keys((new \App\Models\InspeccionTapiceria)->getFillable(), null)));

        if (!$inspeccion->inspeccionAccesorios)
            $inspeccion->setRelation('inspeccionAccesorios', new \App\Models\InspeccionAccesorios(array_fill_keys((new \App\Models\InspeccionAccesorios)->getFillable(), null)));

        if (!$inspeccion->inspeccionParteBaja)
            $inspeccion->setRelation('inspeccionParteBaja', new \App\Models\InspeccionParteBaja(array_fill_keys((new \App\Models\InspeccionParteBaja)->getFillable(), null)));

        if (!$inspeccion->inspeccionVisual)
            $inspeccion->setRelation('inspeccionVisual', new \App\Models\InspeccionVisual(array_fill_keys((new \App\Models\InspeccionVisual)->getFillable(), null)));

        if (!$inspeccion->inspeccionRevisionVisual)
            $inspeccion->setRelation('inspeccionRevisionVisual', new \App\Models\InspeccionRevisionVisual(array_fill_keys((new \App\Models\InspeccionRevisionVisual)->getFillable(), null)));

        if (!$inspeccion->inspeccionRevisionVisualPuntoLiviano)
            $inspeccion->setRelation('inspeccionRevisionVisualPuntoLiviano', new \App\Models\InspeccionRevisionVisualPuntoLiviano(array_fill_keys((new \App\Models\InspeccionRevisionVisualPuntoLiviano)->getFillable(), null)));

        if (!$inspeccion->inspeccionRevisionVisualPuntoMoto)
            $inspeccion->setRelation('inspeccionRevisionVisualPuntoMoto', new \App\Models\InspeccionRevisionVisualPuntoMoto(array_fill_keys((new \App\Models\InspeccionRevisionVisualPuntoMoto)->getFillable(), null)));

        
    }

    return response()->json($ingreso);
}





 public function store(Request $request)
{
    $data = $request->input('inspeccion');

    if (!$data) {
        return response()->json(['message' => 'Datos de inspección no proporcionados.'], 400);
    }

    // Crear Inspección principal
    $inspeccion = Inspeccion::create([
        'ingreso_id'         => $request->input('id'),
        'aseguradora'        => $data['aseguradora'] ?? null,
        'intermediaria'      => $data['intermediaria'] ?? null,
        'combustible'        => $data['combustible'] ?? null,
        'tipo_pintura'       => $data['tipo_pintura'] ?? null,
        'servicio'           => $data['servicio'] ?? null,
        'kilometraje'        => $data['kilometraje'] ?? null,
        'color'              => $data['color'] ?? null,
        'centro_inspeccion'  => $data['centro_inspeccion'] ?? null,
        'valor_mercado'      => $data['valor_mercado'] ?? null,
        'valor_evaluador'    => $data['valor_evaluador'] ?? null,
        'valor_accesorios'   => $data['valor_accesorios'] ?? null,
        'cod_fasecolda'      => $data['cod_fasecolda'] ?? null,
        'valor_fasecolda'    => $data['valor_fasecolda'] ?? null,
        'novedades_inspeccion' => $data['novedades_inspeccion'] ?? null,
        'resultado'          => $data['resultado'] ?? null,
        'turno'              => $data['turno'] ?? null,
        'intermediario'      => $data['intermediario'] ?? null,
        'tipo_vehiculo'     => $data['tipo_vehiculo'] ?? null,
    ]);

    // Crear subcomponentes si existen
    if (isset($data['inspeccion_mecanica'])) {
        $inspeccion->inspeccionMecanica()->create($data['inspeccion_mecanica']);
    }

    if (isset($data['inspeccion_tapiceria'])) {
        $inspeccion->inspeccionTapiceria()->create($data['inspeccion_tapiceria']);
    }

    if (isset($data['inspeccion_funcionamiento'])) {
        $inspeccion->inspeccionFuncionamiento()->create($data['inspeccion_funcionamiento']);
    }

    if (isset($data['inspeccion_luces'])) {
        $inspeccion->inspeccionLuces()->create($data['inspeccion_luces']);
    }

    if (isset($data['inspeccion_exterior'])) {
        $inspeccion->inspeccionExterior()->create($data['inspeccion_exterior']);
    }

    if (isset($data['inspeccion_indicadores'])) {
        $inspeccion->inspeccionIndicadores()->create($data['inspeccion_indicadores']);
    }

    if (isset($data['parte_baja'])) {
        $inspeccion->inspeccionParteBaja()->create($data['parte_baja']);
    }
    if (isset($data['accesorios'])) {
        $inspeccion->inspeccionAccesorios()->createMany($data['accesorios']);
    }
    if (isset($data['inspeccion_visual'])) {
        $inspeccion->inspeccionVisual()->createMany($data['inspeccion_visual']);
    }

    if (isset($data['inspeccion_revision_visual'])) {
        $inspeccion->inspeccionRevisionVisual()->create($data['inspeccion_revision_visual']);
    }

    $this->guardarRevisionVisualPuntoLiviano($inspeccion, $data);
    $this->guardarRevisionVisualPuntoMoto($inspeccion, $data);


    


     $inspec= Inspeccion::with([
            'inspeccionExterior',
            'inspeccionFuncionamiento',
            'inspeccionIndicadores',
            'inspeccionLuces',
            'inspeccionMecanica',
            'inspeccionTapiceria',
            'inspeccionAccesorios',
            'inspeccionParteBaja',
            'inspeccionVisual',
            'inspeccionRevisionVisual',
            'inspeccionRevisionVisualPuntoLiviano',
            'inspeccionRevisionVisualPuntoMoto'
        ])->find($inspeccion->id);
        $ingreso = Ingreso::with('inspeccion','images','historicoPropietarios')->find($inspeccion->ingreso_id);
        
        $user = auth()->user();

        $pdf = Pdf::loadView('pdf.inspeccion',compact('ingreso','inspec','user'));

        // Nombre del archivo
        $nombreArchivo = 'inspeccion_' . $inspeccion->id. '.pdf';

        // Ruta absoluta hacia public/
        $ruta = public_path('documentos/inspecciones/' . $nombreArchivo);

        // Guardar el archivo directamente en public/
        file_put_contents($ruta, $pdf->output());

        $inspeccionupdate=Inspeccion::find($inspeccion->id);
        $inspeccionupdate->file=$nombreArchivo;
        $inspeccionupdate->save();
   

    return response()->json([
        'message' => 'Inspección registrada correctamente.',
        'inspeccion' => $inspeccion->load([
            'inspeccionMecanica',
            'inspeccionTapiceria',
            'inspeccionFuncionamiento',
            'inspeccionLuces',
            'inspeccionExterior',
            'inspeccionIndicadores',
            'inspeccionParteBaja',
            'inspeccionAccesorios',
            'inspeccionVisual',
            'inspeccionRevisionVisual',
            'inspeccionRevisionVisualPuntoLiviano',
            'inspeccionRevisionVisualPuntoMoto'
        ]),
    ], 201);
}

    /**
     * Update an existing Avaluo.
     */
    public function update(Request $request, $id)
    {
        $data = $request->input('inspeccion');

        if (!$data || !isset($data['id'])) {
            return response()->json(['message' => 'Datos de inspección inválidos.'], 400);
        }

        // Buscar inspección principal
        $inspeccion = Inspeccion:: find($data['id']);
        $ingreso=Ingreso::find($inspeccion->ingreso_id);
        $kilometraje=$ingreso->kilometraje;
        

        if (!$inspeccion) {
            return response()->json(['message' => 'Inspección no encontrada.'], 404);
        }

        // Actualizar inspección principal
        $inspeccion->update([
            'aseguradora'        => $data['aseguradora'] ?? null,
            'intermediaria'      => $data['intermediaria'] ?? null,
            'combustible'        => $data['combustible'] ?? null,
            'tipo_pintura'       => $data['tipo_pintura'] ?? null,
            'servicio'           => $data['servicio'] ?? null,
            'kilometraje'        => $kilometraje ?? null,
            'color'              => $data['color'] ?? null,
            'centro_inspeccion'  => $data['centro_inspeccion'] ?? null,
            'valor_mercado'      => $data['valor_mercado'] ?? null,
            'valor_evaluador'    => $data['valor_evaluador'] ?? null,
            'valor_accesorios'   => $data['valor_accesorios'] ?? null,
            'cod_fasecolda'      => $data['cod_fasecolda'] ?? null,
            'valor_fasecolda'    => $data['valor_fasecolda'] ?? null,
            'novedades_inspeccion' => $data['novedades_inspeccion'] ?? null,
            'resultado'          => $data['resultado'] ?? null,
            'turno'              => $data['turno'] ?? null,
            'intermediario'      => $data['intermediario'] ?? null,
            'ciudad'      => $data['ciudad'] ?? null,
            'observaciones'      => $data['observaciones'] ?? null,
            'expide_para'      => $data['expide_para'] ?? null,
            'tipo_vehiculo' => $data['tipo_vehiculo'] ?? null,
        ]);

        // Actualizar relaciones si existen en el payload
        $relaciones = [
            'inspeccion_mecanica'     => 'inspeccionMecanica',
            'inspeccion_tapiceria'    => 'inspeccionTapiceria',
            'inspeccion_funcionamiento' => 'inspeccionFuncionamiento',
            'inspeccion_luces'        => 'inspeccionLuces',
            'inspeccion_exterior'     => 'inspeccionExterior',
            'inspeccion_parte_baja'   => 'inspeccionParteBaja',
            'inspeccion_indicadores'  => 'inspeccionIndicadores',
            'inspeccion_revision_visual' => 'inspeccionRevisionVisual'
        ];

        foreach ($relaciones as $campo => $relacion) {
            if (isset($data[$campo])) {
                $submodelo = $inspeccion->$relacion;
                if ($submodelo) {
                    $submodelo->update($data[$campo]);
                } else {
                    $inspeccion->$relacion()->create($data[$campo]);
                }
            }
        }

        $this->guardarRevisionVisualPuntoLiviano($inspeccion, $data);
        $this->guardarRevisionVisualPuntoMoto($inspeccion, $data);

        // Actualizar accesorios (borrar y crear nuevos si se envían)
        if (isset($data['accesorios']) && is_array($data['accesorios'])) {
            $inspeccion->inspeccionAccesorios()->delete();
            foreach ($data['accesorios'] as $accesorio) {
                $inspeccion->inspeccionAccesorios()->create($accesorio);
            }
        }

        if (isset($data['inspeccion_visual']) && is_array($data['inspeccion_visual'])) {
            $inspeccion->inspeccionVisual()->delete();
            foreach ($data['inspeccion_visual'] as $inspeccion_visual) {
                $inspeccion->inspeccionVisual()->create($inspeccion_visual);
            }
        }

        $inspec= Inspeccion::with([
            'inspeccionExterior',
            'inspeccionFuncionamiento',
            'inspeccionIndicadores',
            'inspeccionLuces',
            'inspeccionMecanica',
            'inspeccionTapiceria',
            'inspeccionAccesorios',
            'inspeccionParteBaja',
            'inspeccionVisual',
            'inspeccionRevisionVisual',
            'inspeccionRevisionVisualPuntoLiviano',
            'inspeccionRevisionVisualPuntoMoto'
        ])->find($inspeccion->id);
        $ingreso = Ingreso::with('inspeccion','images','historicoPropietarios')->find($request->id);
        
        $user = auth()->user();

        $pdf = Pdf::loadView('pdf.inspeccion',compact('ingreso','inspec','user'));

        // Nombre del archivo
        $nombreArchivo = 'inspeccion_' . $inspeccion->id. '.pdf';

        // Ruta absoluta hacia public/
        $ruta = public_path('documentos/inspecciones/' . $nombreArchivo);

        // Guardar el archivo directamente en public/
        file_put_contents($ruta, $pdf->output());

        $inspeccionupdate=Inspeccion::find($inspeccion->id);
        $inspeccionupdate->file=$nombreArchivo;
        $inspeccionupdate->save();

        return response()->json([
            'message' => 'Inspección actualizada correctamente.',
            'inspeccion' => $inspeccion->load([
                'inspeccionMecanica',
                'inspeccionTapiceria',
                'inspeccionFuncionamiento',
                'inspeccionLuces',
                'inspeccionExterior',
                'inspeccionIndicadores',
                'inspeccionParteBaja',
                'inspeccionAccesorios',
                'inspeccionVisual',
                'inspeccionRevisionVisual',
                'inspeccionRevisionVisualPuntoLiviano',
                'inspeccionRevisionVisualPuntoMoto',
            ]),
        ]);
    }

    // Eliminar un avalúo
    public function destroy($id)
    {
        $avaluo = Ingreso::find($id);

        if (!$avaluo) {
            return response()->json(['message' => 'Avalúo no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $avaluo->delete();

        return response()->json(['message' => 'Avalúo eliminado correctamente']);
    }

    // =======================
    // Funciones privadas
    // =======================

    private function validateAvaluo(Request $request)
    {
        return $request->validate([
            'datosGenerales.tiposervicio'=> 'nullable|string',
            'datosGenerales.solicitante' => 'nullable|string',
            'datosGenerales.documentoSolicitante' => 'nullable|string',
            'datosGenerales.direccion_solicitante' => 'nullable|string',
            'datosGenerales.telefono_solicitante' => 'nullable|string',
            'datosGenerales.placa' => 'nullable|string',
            'datosGenerales.ubicacionActivo' => 'nullable|string',
            'datosGenerales.fechaSolicitud' => 'nullable|date',
            'datosGenerales.fechaInspeccion' => 'nullable|date',
            'datosGenerales.fechaInforme' => 'nullable|date',
            'datosGenerales.objetoAvaluo' => 'nullable|string',
            'datosGenerales.codigoInternoMovil' => 'nullable|string',
            'datosGenerales.estado'=> 'nullable|string',

            'informacionBien.tipoPropiedad' => 'nullable|string',
            'informacionBien.fechaMatricula' => 'nullable|date',
            'informacionBien.movil' => 'nullable|string',
            'informacionBien.marca' => 'nullable|string',
            'informacionBien.linea' => 'nullable|string',
            'informacionBien.clase' => 'nullable|string',
            'informacionBien.tipoCarroceria' => 'nullable|string',
            'informacionBien.categoria' => 'nullable|string',
            'informacionBien.color' => 'nullable|string',
            'informacionBien.cilindraje' => 'nullable|integer',
            'informacionBien.modelo' => 'nullable|integer',
            'informacionBien.kilometraje' => 'nullable|integer',
            'informacionBien.cajaCambios' => 'nullable|string',
            'informacionBien.tipoTraccion' => 'nullable|string',
            'informacionBien.numeroPasajeros' => 'nullable|integer',
            'informacionBien.capacidadCarga' => 'nullable|integer',
            'informacionBien.llantaDelanteraIzquierda' => 'nullable|string',
            'informacionBien.llantaDelanteraDerecha' => 'nullable|string',
            'informacionBien.llantaTraseraIzquierda' => 'nullable|string',
            'informacionBien.llantaTraseraDerecha' => 'nullable|string',
            'informacionBien.llantaRepuesto' => 'nullable|string',
            'informacionBien.numeroChasis' => 'nullable|string',
            'informacionBien.numeroSerie' => 'nullable|string',
            'informacionBien.numeroMotor' => 'nullable|string',
            'informacionBien.nacionalidad' => 'nullable|string',
            'informacionBien.propietario' => 'nullable|string',
            'informacionBien.empresaAfiliacion' => 'nullable|string',
            'informacionBien.ciudad_registro' => 'nullable|string',
            'informacionBien.no_licencia' => 'nullable|string',
            'informacionBien.fecha_expedicion_licencia' => 'nullable|string',
            'informacionBien.organismo_transito' => 'nullable|string',
            'informacionBien.soat' => 'nullable|string',
            'informacionBien.fecha_expedicion_soat' => 'nullable|date',
            'informacionBien.fecha_inicio_vigencia_soat' => 'nullable|date',
            'informacionBien.fecha_vencimiento_soat' => 'nullable|date',
            'informacionBien.entidad_expide_soat' => 'nullable|string',
            'informacionBien.estado_soat' => 'nullable|string',
            'informacionBien.rtm' => 'nullable|string',
            'informacionBien.fecha_vencimiento_rtm' => 'nullable|date',
            'informacionBien.centro_revision_rtm' => 'nullable|string',
            'informacionBien.estado_rtm' => 'nullable|string',
        ]);
    }

    private function mapAvaluoData(array $data)
    {
        return [
            'tiposervicio' =>$data['datosGenerales']['tiposervicio'] ?? null,
            'solicitante' => $data['datosGenerales']['solicitante'] ?? null,
            'documento_solicitante' => $data['datosGenerales']['documentoSolicitante'] ?? null,
            'direccion_solicitante' => $data['datosGenerales']['direccion_solicitante'] ?? null,
            'telefono_solicitante' => $data['datosGenerales']['telefono_solicitante'] ?? null,
            'placa' => $data['datosGenerales']['placa'] ?? null,
            'ubicacion_activo' => $data['datosGenerales']['ubicacionActivo'] ?? null,
            'fecha_solicitud' => $data['datosGenerales']['fechaSolicitud'] ?? null,
            'fecha_inspeccion' => $data['datosGenerales']['fechaInspeccion'] ?? null,
            'fecha_informe' => $data['datosGenerales']['fechaInforme'] ?? null,
            'objeto_avaluo' => $data['datosGenerales']['objetoAvaluo'] ?? null,
            'codigo_interno_movil' => $data['datosGenerales']['codigoInternoMovil'] ?? null,
            'estado' => 'En Inspección',
            

            'tipo_propiedad' => $data['informacionBien']['tipoPropiedad'] ?? null,
            'fecha_matricula' => $data['informacionBien']['fechaMatricula'] ?? null,
            'movil' => $data['informacionBien']['movil'] ?? null,
            'marca' => $data['informacionBien']['marca'] ?? null,
            'linea' => $data['informacionBien']['linea'] ?? null,
            'clase' => $data['informacionBien']['clase'] ?? null,
            'tipo_carroceria' => $data['informacionBien']['tipoCarroceria'] ?? null,
            'categoria' => $data['informacionBien']['categoria'] ?? null,
            'color' => $data['informacionBien']['color'] ?? null,
            'cilindraje' => $data['informacionBien']['cilindraje'] ?? null,
            'modelo' => $data['informacionBien']['modelo'] ?? null,
            'kilometraje' => $data['informacionBien']['kilometraje'] ?? null,
            'caja_cambios' => $data['informacionBien']['cajaCambios'] ?? null,
            'tipo_traccion' => $data['informacionBien']['tipoTraccion'] ?? null,
            'numero_pasajeros' => $data['informacionBien']['numeroPasajeros'] ?? null,
            'capacidad_carga' => $data['informacionBien']['capacidadCarga'] ?? null,
            'llanta_delantera_izquierda' => $data['informacionBien']['llantaDelanteraIzquierda'] ?? null,
            'llanta_delantera_derecha' => $data['informacionBien']['llantaDelanteraDerecha'] ?? null,
            'llanta_trasera_izquierda' => $data['informacionBien']['llantaTraseraIzquierda'] ?? null,
            'llanta_trasera_derecha' => $data['informacionBien']['llantaTraseraDerecha'] ?? null,
            'llanta_repuesto' => $data['informacionBien']['llantaRepuesto'] ?? null,
            'numero_chasis' => $data['informacionBien']['numeroChasis'] ?? null,
            'numero_serie' => $data['informacionBien']['numeroSerie'] ?? null,
            'numero_motor' => $data['informacionBien']['numeroMotor'] ?? null,
            'nacionalidad' => $data['informacionBien']['nacionalidad'] ?? null,
            'propietario' => $data['informacionBien']['propietario'] ?? null,
            'empresa_afiliacion' => $data['informacionBien']['empresaAfiliacion'] ?? null,
            'ciudad_registro' => $data['informacionBien']['ciudad_registro'] ?? null,
            'no_licencia' => $data['informacionBien']['no_licencia'] ?? null,
            'fecha_expedicion_licencia' => $data['informacionBien']['fecha_expedicion_licencia'] ?? null,
            'organismo_transito' => $data['informacionBien']['organismo_transito'] ?? null,
            'soat' => $data['informacionBien']['soat'] ?? null,
            'fecha_expedicion_soat' =>  $data['informacionBien']['fecha_expedicion_soat'] ?? null,
            'fecha_inicio_vigencia_soat' =>  $data['informacionBien']['fecha_inicio_vigencia_soat'] ?? null,
            'fecha_vencimiento_soat' =>  $data['informacionBien']['fecha_vencimiento_soat'] ?? null,
            'entidad_expide_soat' =>  $data['informacionBien']['entidad_expide_soat'] ?? null,
            'estado_soat' =>  $data['informacionBien']['estado_soat'] ?? null,
            'rtm' => $data['informacionBien']['rtm'] ?? null,
            'fecha_vencimiento_rtm' => $data['informacionBien']['fecha_vencimiento_rtm'] ?? null,
            'centro_revision_rtm' => $data['informacionBien']['centro_revision_rtm'] ?? null,
            'estado_rtm' => $data['informacionBien']['estado_rtm'] ?? null,
        ];
    }

  public function indexv2()
    {
        // Aquí defines un array con datos, pero no lo estás usando en la vista aún
        $html= '';

        // Retornas la vista ubicada en resources/views/pdf/avaluo.blade.php
        // y le pasas los datos con compact()
        return view('pdf.avaluo', compact('html'));
    }

}
