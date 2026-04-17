<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Models\Avaluo;
use App\Models\FasecoldaValor;
use App\Models\ValoresRepuesto;
use App\Models\User;
use App\Models\IngresoImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use ZipArchive;

class AvaluoController extends Controller
{

    private const ESTADOS_COMPONENTES_POR_DEFECTO = [
        'latoneria_estado' => 'Regular',
        'pintura_estado' => 'Regular',
        'tapiceria_estado' => 'Malo',
        'motor_estado' => 'Inoperativo',
        'chasis_estado' => 'Regular',
        'transmision_estado' => 'Malo',
        'frenos_estado' => 'Malo',
        'refrigeracion_estado' => 'No Aplica',
        'electrico_estado' => 'Regular',
        'tanque_estado' => 'Regular',
        'bateria_estado' => 'No Sirve',
        'llantas_estado' => 'Malas',
        'llave_estado' => 'No Presenta',
        'vidrios_estado' => 'No Aplica',
    ];

    private const CAMPOS_MASIVOS_PERMITIDOS = [
        'codigo_fasecolda',
        'valor_chatarra_kg',
        'ubicacion',
        'evaluador',
        'tipo',
        'chatarra',
        'peso_chatarra_kg',
        'observaciones',
        'cilindraje',
        'fecha_inspeccion',
        'tipo_vehiculo',
        'es_repuesto_especial',
    ];
    // Obtener listado paginado con búsqueda
    public function index(Request $request)
    {
        $query = Ingreso::query();

        // Filtro principal: tipo de servicio
        if ($request->has('tipo') && !empty($request->tipo)) {
            //$query->where('tiposervicio', $request->tipo);
            dd($request->tipo);
            if($request->tipo=='Sec Bogota'){
                $query->whereIn('tiposervicio', ['Sec Bogota']);
            }else{
                $query->whereIn('tiposervicio', [$request->tipo,'Avaluo e Inspección']);
            }
            
            
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
        $ingreso = Ingreso::with('avaluo','avaluo.clasificados','avaluo.corregidos','avaluo.limitaciones')->find($id);

        if (!$ingreso) {
            return response()->json(['message' => 'Ingreso no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Si no existe el avaluo, devolvemos un objeto vacío con los atributos del modelo
        if (!$ingreso->avaluo) {
            $avaluo = new \App\Models\Avaluo();

            // Forzar que se vean los atributos vacíos (null)
            $avaluo->fill(array_fill_keys($avaluo->getFillable(), null));

            $ingreso->setRelation('avaluo', $avaluo);
        }

        return response()->json($ingreso);
    }

    public function store(Request $request)
    {
        

        if ($request->tiposervicio === "Sec Bogota") {

            // Mapeo del request al modelo Ingreso
            $ingresoData = [
                'tiposervicio'              => $request->tiposervicio,
                'solicitante'               => $request->solicitante,
                'documento_solicitante'     => $request->documento_solicitante,
                'direccion_solicitante'     => $request->direccion_solicitante,
                'telefono_solicitante'      => $request->telefono_solicitante,
                'placa'                     => $request->placa,
                'ubicacion_activo'          => $request->ubicacion_activo,
                'fecha_solicitud'           => $request->fechaSolicitud,
                'fecha_inspeccion'          => $request->fechaInspeccion,
                'fecha_informe'             => $request->fecha_informe,
                'objeto_avaluo'             => $request->objeto_avaluo,
                'codigo_interno_movil'      => $request->codigo_interno_movil,
                'tipo_propiedad'            => $request->tipo_propiedad,
                'fecha_matricula'           => $request->fecha_matricula,
                'movil'                     => $request->movil,
                'marca'                     => $request->marca,
                'linea'                     => $request->linea,
                'clase'                     => $request->clase,
                'tipo_carroceria'           => $request->tipoCarroceria,
                'categoria'                 => $request->categoria,
                'color'                     => $request->color,
                'cilindraje'                => $request->cilindraje,
                'modelo'                    => $request->modelo,
                'kilometraje'               => $request->kilometraje,
                'caja_cambios'              => $request->cajaCambios,
                'tipo_traccion'             => $request->tipo_traccion,
                'numero_pasajeros'          => $request->numero_pasajeros,
                'capacidad_carga'           => $request->capacidad_carga,
                'llanta_delantera_izquierda'=> $request->llanta_delantera_izquierda,
                'llanta_delantera_derecha'  => $request->llanta_delantera_derecha,
                'llanta_trasera_izquierda'  => $request->llanta_trasera_izquierda,
                'llanta_trasera_derecha'    => $request->llanta_trasera_derecha,
                'llanta_repuesto'           => $request->llanta_repuesto,
                'numero_chasis'             => $request->numeroChasis,
                'numero_serie'              => $request->numeroSerie,
                'numero_motor'              => $request->numeroMotor,
                'nacionalidad'              => $request->nacionalidad,
                'propietario'               => $request->propietario,
                'empresa_afiliacion'        => $request->empresa_afiliacion,
                'soat'                      => $request->soat,
                'fecha_expedicion_soat'     => $request->fecha_expedicion_soat,
                'fecha_inicio_vigencia_soat'=> $request->fecha_inicio_vigencia_soat,
                'fecha_vencimiento_soat'    => $request->fecha_vencimiento_soat,
                'entidad_expide_soat'       => $request->entidad_expide_soat,
                'estado_soat'               => $request->estado_soat,
                'rtm'                       => $request->rtm,
                'fecha_vencimiento_rtm'     => $request->fecha_vencimiento_rtm,
                'centro_revision_rtm'       => $request->centro_revision_rtm,
                'estado_rtm'                => $request->estado_rtm,
                'ciudad_registro'           => $request->ciudad_registro,
                'no_licencia'               => $request->no_licencia,
                'fecha_expedicion_licencia' => $request->fecha_expedicion_licencia,
                'organismo_transito'        => $request->organismo_transito,
                'estado'                    => $request->estado,
                'fecha_inicial_matricula'   => $request->fecha_inicial_matricula,
                'estado_matricula'          => $request->estado_matricula,
                'traslados_matricula'       => $request->traslados_matricula,
                'tipo_servicio_vehiculo'    => $request->tipo_servicio_vehiculo,
                'cambios_tipo_servicio'     => $request->cambios_tipo_servicio,
                'fecha_ult_cambio_servicio' => $request->fecha_ult_cambio_servicio,
                'cambio_color_historica'    => $request->cambio_color_historica,
                'fecha_ult_cambio_color'    => $request->fecha_ult_cambio_color,
                'color_cambiado'            => $request->color_cambiado,
                'cambios_blindaje'          => $request->cambios_blindaje,
                'fecha_cambio_blindaje'     => $request->fecha_cambio_blindaje,
                'repotenciado'              => $request->repotenciado,
                'tiene_gravamedes'          => $request->tiene_gravamedes,
                'tiene_prenda'              => $request->tiene_prenda,
                'regrabado_no_motor'        => $request->regrabado_no_motor,
                'regrabado_no_chasis'       => $request->regrabado_no_chasis,
                'regrabado_no_serie'        => $request->regrabado_no_serie,
                'regrabado_no_vin'          => $request->regrabado_no_vin,
                'limitacion_propiedad'      => $request->limitacion_propiedad,
                'numero_doc_proceso'        => $request->numero_doc_proceso,
                'entidad_juridica'          => $request->entidad_juridica,
                'tipo_doc_demandante'       => $request->tipo_doc_demandante,
                'no_identificacion_demandante' => $request->no_identificacion_demandante,
                'fecha_expedicion_novedad'  => $request->fecha_expedicion_novedad,
                'fecha_radicacion'          => $request->fecha_radicacion,
                'cantidad_ejes'             => $request->cantidad_ejes,
                'peso_bruto'                => $request->peso_bruto,
                'numeroVin'                 => $request->numeroVin,
                'documento_propietario'     => $request->documento_propietario,
                'peso_mermado'=> $request->peso_mermado,
                'estado_registro_runt'=> $request->estado_registro_runt,
                'capacidad_ton'=> $request->capacidad_ton,
            ];

            // Crear ingreso automáticamente
            $ingreso = Ingreso::create($ingresoData);

            // Sobrescribir ingreso_id en el request para el avalúo
            $request->merge([
                'avaluo' => array_merge($request->avaluo, [
                    'ingreso_id' => $ingreso->id
                ])
            ]);

        }


        // Si el servicio es Sec Bogota, crear ingreso automáticamente
        if ($request->tiposervicio === "Sec Bogota") {

        // Buscar el último avalúo con tiposervicio = Sec Bogota
        $ultimoAvaluo = Avaluo::whereHas('ingreso', function ($q) {
            $q->where('tiposervicio', 'Sec Bogota');
        })->orderBy('code_movilidad', 'DESC')->first();

        // Calcular el nuevo code_movilidad
        $nuevoCodeMovilidad = $ultimoAvaluo ? ($ultimoAvaluo->code_movilidad + 1) : 1;

        // Inyectar code_movilidad dentro del array de avalúo
        $request->merge([
            'avaluo' => array_merge($request->avaluo, [
                'code_movilidad' => $nuevoCodeMovilidad
            ])
        ]);
    }


        // Validaciones estrictas
        $validated = $request->validate([
            'avaluo.ingreso_id' => 'required|exists:ingresos,id',
            'avaluo.vida_util_probable' => 'nullable|numeric',
            'avaluo.vida_usada_dias' => 'nullable|numeric',
            'avaluo.vida_usada_meses' => 'nullable|numeric',
            'avaluo.vida_usada_anos' => 'nullable|numeric',
            'avaluo.vida_util_remate' => 'nullable|numeric',
            'avaluo.vida_util_anos' => 'nullable|numeric',
            'avaluo.antiguedad' => 'nullable|numeric',
            'avaluo.vida_util' => 'nullable|numeric',
            'avaluo.valor_reposicion' => 'nullable|numeric',
            'avaluo.valor_residual' => 'nullable|numeric',
            'avaluo.estado_conservacion' => 'nullable|string|max:255',
            'avaluo.x' => 'nullable|numeric',
            'avaluo.k' => 'nullable|numeric',
            'avaluo.valor_resonable' => 'nullable|numeric',
            'avaluo.capacidad_transportadora' => 'nullable|string',
            'avaluo.valor_razonable' => 'nullable|numeric',
            'avaluo.valor_carroceria' => 'nullable|numeric',
            'avaluo.valor_reparaciones' => 'nullable|numeric',
            'avaluo.valor_llantas' => 'nullable|numeric',
            'avaluo.valor_pintura' => 'nullable|numeric',
            'avaluo.valor_overhaul_motor' => 'nullable|numeric',
            'avaluo.factor_demerito' => 'nullable|numeric',
            'avaluo.valor_accesorios' => 'nullable|numeric',
            'avaluo.indice_responsabilidad_minimo' => 'nullable|numeric',
            'avaluo.avaluo_total' => 'nullable|numeric',
            'avaluo.no_factura' => 'nullable|string|max:255',
            'avaluo.declaracion_importacion' => 'nullable|string|max:255',
            'avaluo.fecha_importacion' => 'nullable|date',
            'avaluo.registro_maquinaria' => 'nullable|string|max:255',
            'avaluo.gps' => 'nullable|string|max:255',
            'avaluo.llanta_delantera_izquierda'=>'nullable',
            'avaluo.llanta_delantera_derecha'=>'nullable',
            'avaluo.llanta_trasera_izquierda'=>'nullable',
            'avaluo.llanta_trasera_derecha'=>'nullable',
            'avaluo.llanta_repuesto'=>'nullable',
            'avaluo.tipo'=>'nullable',
            'avaluo.formato'=>'nullable',
            'avaluo.observaciones'=>'nullable',
            'avaluo.latoneria_estado'=>'nullable', 
            'avaluo.latoneria_valor'=>'nullable',
            'avaluo.pintura_estado'=>'nullable',
            'avaluo.tapiceria_estado'=>'nullable', 
            'avaluo.tapiceria_valor'=>'nullable',
            'avaluo.motor_estado'=>'nullable', 
            'avaluo.motor_valor'=>'nullable',
            'avaluo.chasis_estado'=>'nullable', 
            'avaluo.chasis_valor'=>'nullable',
            'avaluo.transmision_estado'=>'nullable', 
            'avaluo.transmision_valor'=>'nullable',
            'avaluo.frenos_estado'=>'nullable', 
            'avaluo.frenos_valor'=>'nullable',
            'avaluo.refrigeracion_estado'=>'nullable', 
            'avaluo.refrigeracion_valor'=>'nullable',
            'avaluo.electrico_estado'=>'nullable', 
            'avaluo.electrico_valor'=>'nullable',
            'avaluo.tanque_estado'=>'nullable', 
            'avaluo.tanque_valor'=>'nullable',
            'avaluo.bateria_estado'=>'nullable', 
            'avaluo.bateria_valor'=>'nullable',
            'avaluo.llantas_estado'=>'nullable', 
            'avaluo.llantas_valor'=>'nullable',
            'avaluo.llave_estado'=>'nullable',
            'avaluo.llave_valor'=>'nullable',
            'avaluo.vidrios_estado'=>'nullable', 
            'avaluo.vidrios_valor'=>'nullable',
            'avaluo.chatarra'=>'nullable',
            'avaluo.valor_chatarra_kg'=>'nullable',
            'avaluo.peso_chatarra_kg'=>'nullable',
            'avaluo.valor_RTM'=>'nullable',
            'avaluo.valor_SOAT'=>'nullable',
            'avaluo.valor_faltantes'=>'nullable',
            'avaluo.codigo_fasecolda'=>'nullable',
            'avaluo.fecha_inmovilizacion' => 'nullable|date',
            'avaluo.dias_inmovilizacion' => 'nullable|integer|min:0',
            'avaluo.porc_reposicion'=>'nullable',
            'avaluo.ubicacion'=>'nullable',
            'avaluo.evaluador'=>'nullable',
            'avaluo.code_movilidad' => 'nullable|numeric',
            'avaluo.cerrado' => 'nullable|boolean',
            

        ]);

        // Extraer únicamente el bloque de avaluo
        $data = $validated['avaluo'];
        if (!array_key_exists('dias_inmovilizacion', $data) || $data['dias_inmovilizacion'] === null) {
            $data['dias_inmovilizacion'] = $this->calcularDiasInmovilizacion(
                $data['fecha_inmovilizacion'] ?? null,
                $request->input('fecha_inspeccion')
            );
        }

        $avaluo = Avaluo::create($data);

        // Guardar clasificados
        if ($request->has('avaluo.clasificados')) {
            foreach ($request->input('avaluo.clasificados') as $item) {
                $avaluo->clasificados()->create($item);
            }
        }

        // Guardar corregidos
        if ($request->has('avaluo.corregidos')) {
            foreach ($request->input('avaluo.corregidos') as $item) {
                $avaluo->corregidos()->create($item);
            }
        }

        $graficaPath = $this->generarGraficaDispercion($avaluo);
        $ingreso = Ingreso::with('avaluo','images')->find($avaluo->ingreso_id);
        $user = auth()->user();

        if($avaluo->tipo=='jans'){
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            // Dataset corregidos desde el Avaluo
            $corregidos = collect($ingreso->avaluo->corregidos)->map(fn($c) => [
                'x' => (int) $c->modelo,
                'y' => (float) $c->valor
            ])->toArray();

            // El modelo que quiero consultar está en el Ingreso, no en el Avaluo
            $modeloConsultar = (int) $ingreso->modelo;

            // Calcular fórmula y valor estimado para ese modelo
            $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);

            //$pdf = Pdf::loadView('pdf.avaluojans',compact('ingreso','avaluo','graficaPath','resultado','user'));

            if($request->tiposervicio === "Sec Bogota"){
                $pdf = Pdf::loadView('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }else{
                $pdf = Pdf::loadView('pdf.avaluojans',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }
        }else{            
            if($request->tiposervicio === "Sec Bogota"){
                $graficaPath = null;
                $resultado = null;
                $pdf = Pdf::loadView('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }else{
                $pdf = Pdf::loadView('pdf.avaluo',compact('ingreso','avaluo','user'));
            }
        }
        // Nombre del archivo
        $nombreArchivo = 'documento_' . now()->format('Ymd_His') . '.pdf';
        // Ruta absoluta hacia public/
        //$ruta = public_path('documentos/' . $nombreArchivo);
        // Guardar el archivo directamente en public/
        //file_put_contents($ruta, $pdf->output());

        $avaluoupdate=Avaluo::find($avaluo->id);
        $avaluoupdate->file=$nombreArchivo;
        $avaluoupdate->save();

        return response()->json($avaluo, 201);
    }

    /**
     * Update an existing Avaluo.
     */
    public function update(Request $request, Avaluo $avaluo)
    {
        $validated = $request->validate([
            'avaluo.ingreso_id' => 'required|exists:ingresos,id',
            'avaluo.vida_util_probable' => 'nullable|numeric',
            'avaluo.vida_usada_dias' => 'nullable|numeric',
            'avaluo.vida_usada_meses' => 'nullable|numeric',
            'avaluo.vida_usada_anos' => 'nullable|numeric',
            'avaluo.vida_util_remate' => 'nullable|numeric',
            'avaluo.vida_util_anos' => 'nullable|numeric',
            'avaluo.antiguedad' => 'nullable|numeric',
            'avaluo.vida_util' => 'nullable|numeric',
            'avaluo.valor_reposicion' => 'nullable|numeric',
            'avaluo.valor_residual' => 'nullable|numeric',
            'avaluo.estado_conservacion' => 'nullable|string|max:255',
            'avaluo.x' => 'nullable|numeric',
            'avaluo.k' => 'nullable|numeric',
            'avaluo.valor_resonable' => 'nullable|numeric',
            'avaluo.capacidad_transportadora' => 'nullable|string',
            'avaluo.valor_razonable' => 'nullable|numeric',
            'avaluo.valor_carroceria' => 'nullable|numeric',
            'avaluo.valor_reparaciones' => 'nullable|numeric',
            'avaluo.valor_llantas' => 'nullable|numeric',
            'avaluo.valor_pintura' => 'nullable|numeric',
            'avaluo.valor_overhaul_motor' => 'nullable|numeric',
            'avaluo.factor_demerito' => 'nullable|numeric',
            'avaluo.valor_accesorios' => 'nullable|numeric',
            'avaluo.indice_responsabilidad_minimo' => 'nullable|numeric',
            'avaluo.avaluo_total' => 'nullable|numeric',
            'avaluo.no_factura' => 'nullable|string|max:255',
            'avaluo.declaracion_importacion' => 'nullable|string|max:255',
            'avaluo.fecha_importacion' => 'nullable|date',
            'avaluo.registro_maquinaria' => 'nullable|string|max:255',
            'avaluo.gps' => 'nullable|string|max:255',
            'avaluo.fecha_inspeccion'=>'nullable',
            'avaluo.llanta_delantera_izquierda'=>'nullable',
            'avaluo.llanta_delantera_derecha'=>'nullable',
            'avaluo.llanta_trasera_izquierda'=>'nullable',
            'avaluo.llanta_trasera_derecha'=>'nullable',
            'avaluo.llanta_repuesto'=>'nullable',
            'avaluo.tipo'=>'nullable',
            'avaluo.formato'=>'nullable',
            'avaluo.observaciones'=>'nullable',
            'avaluo.latoneria_estado'=>'nullable', 
            'avaluo.latoneria_valor'=>'nullable',
            'avaluo.pintura_estado'=>'nullable',
            'avaluo.tapiceria_estado'=>'nullable', 
            'avaluo.tapiceria_valor'=>'nullable',
            'avaluo.motor_estado'=>'nullable', 
            'avaluo.motor_valor'=>'nullable',
            'avaluo.chasis_estado'=>'nullable', 
            'avaluo.chasis_valor'=>'nullable',
            'avaluo.transmision_estado'=>'nullable', 
            'avaluo.transmision_valor'=>'nullable',
            'avaluo.frenos_estado'=>'nullable', 
            'avaluo.frenos_valor'=>'nullable',
            'avaluo.refrigeracion_estado'=>'nullable', 
            'avaluo.refrigeracion_valor'=>'nullable',
            'avaluo.electrico_estado'=>'nullable', 
            'avaluo.electrico_valor'=>'nullable',
            'avaluo.tanque_estado'=>'nullable', 
            'avaluo.tanque_valor'=>'nullable',
            'avaluo.bateria_estado'=>'nullable', 
            'avaluo.bateria_valor'=>'nullable',
            'avaluo.llantas_estado'=>'nullable', 
            'avaluo.llantas_valor'=>'nullable',
            'avaluo.llave_estado'=>'nullable',
            'avaluo.llave_valor'=>'nullable',
            'avaluo.vidrios_estado'=>'nullable', 
            'avaluo.vidrios_valor'=>'nullable',
            'avaluo.chatarra'=>'nullable',
            'avaluo.valor_chatarra_kg'=>'nullable',
            'avaluo.peso_chatarra_kg'=>'nullable',
            'avaluo.valor_RTM'=>'nullable',
            'avaluo.valor_SOAT'=>'nullable',
            'avaluo.valor_faltantes'=>'nullable',
            'avaluo.codigo_fasecolda'=>'nullable',
            'avaluo.fecha_inmovilizacion' => 'nullable|date',
            'avaluo.dias_inmovilizacion' => 'nullable|integer|min:0',
            'avaluo.porc_reposicion'=>'nullable',
            'avaluo.ubicacion'=>'nullable',
            'avaluo.evaluador'=>'nullable',
            'avaluo.cerrado' => 'nullable|boolean',
        ]);

        $data = $validated['avaluo'];
        $fechaInspeccionReferencia = $request->input('fecha_inspeccion')
            ?? optional($avaluo->ingreso)->fecha_inspeccion;
        if (!array_key_exists('dias_inmovilizacion', $data) || $data['dias_inmovilizacion'] === null) {
            $data['dias_inmovilizacion'] = $this->calcularDiasInmovilizacion(
                $data['fecha_inmovilizacion'] ?? ($avaluo->fecha_inmovilizacion ?? null),
                $fechaInspeccionReferencia
            );
        }

        if ($avaluo->cerrado && !array_key_exists('cerrado', $data)) {
            return response()->json([
                'message' => 'El avalúo está cerrado y no se puede modificar.'
            ], Response::HTTP_LOCKED);
        }

        if (
            $avaluo->cerrado &&
            array_key_exists('cerrado', $data) &&
            filter_var($data['cerrado'], FILTER_VALIDATE_BOOLEAN) === true
        ) {
            return response()->json([
                'message' => 'El avalúo está cerrado y no se puede modificar.'
            ], Response::HTTP_LOCKED);
        }

        
        //return response()->json(['message' => $validated]);

        $avaluo->update($data);
        if($avaluo->user_id==null){
            $avaluo->user_id=auth()->user()->id;
            $avaluo->save();
        }

        $ingreso = Ingreso::with('avaluo','images')->find($request->id);
        
        if($ingreso->tiposervicio === "Sec Bogota"){

            $ingresoData = [
                'marca' => $request->marca,
                'linea'=>$request->linea,
                'fecha_matricula'=>$request->fecha_matricula,
                'clase'=>$request->clase,
                'tipo_carroceria'=>$request->tipo_carroceria,
                'color'=>$request->color,
                'cilindraje'=>$request->cilindraje,
                'modelo'=>$request->modelo,
                'kilometraje'=>$request->kilometraje,
                'caja_cambios'=>$request->caja_cambios,
                'numero_chasis'=>$request->numero_chasis,
                'numero_serie'=>$request->numero_serie,
                'numero_motor'=>$request->numero_motor,
                'numeroVin'=>$request->numeroVin,
                'tipo_servicio_vehiculo'=>$request->tipo_servicio_vehiculo,
                'cantidad_ejes'=>$request->cantidad_ejes,
                'peso_bruto'=>$request->peso_bruto,
                'peso_mermado'=>$request->peso_mermado,
                'numero_pasajeros'=>$request->numero_pasajeros,
                'estado_registro_runt'=>$request->estado_registro_runt,
                'capacidad_ton'=>$request->capacidad_ton,
                'fecha_inspeccion'=>$request->fecha_inspeccion,
                'fecha_solicitud'=>$request->fecha_solicitud,
            ];

            $ingresoupdate = Ingreso::find($avaluo->ingreso_id);
            $ingresoupdate->update($ingresoData);

        }
        
        
        $this->asignarConsecutivoEInicialSiAplica($avaluo);


        

        
        // Guardar clasificados
        if ($request->has('avaluo.clasificados')) {
            $avaluo->clasificados()->delete();
            foreach ($request->input('avaluo.clasificados') as $item) {
                $avaluo->clasificados()->create($item);
            }
        }

        // Guardar corregidos
        if ($request->has('avaluo.corregidos')) {
            $avaluo->corregidos()->delete();
            foreach ($request->input('avaluo.corregidos') as $item) {
                $avaluo->corregidos()->create($item);
            }
        }

        if ($request->has('avaluo.limitaciones')) {
            $avaluo->limitaciones()->delete();
            foreach ($request->input('avaluo.limitaciones') as $item) {
                $avaluo->limitaciones()->create($item);
            }
        }

        

        $ingreso = Ingreso::with('avaluo','images')->find($request->id);
        $user = User::find($avaluo->user_id);
        if($avaluo->tipo=='comercial'){
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            // Dataset corregidos desde el Avaluo
            $corregidos = collect($ingreso->avaluo->corregidos)->map(fn($c) => [
                'x' => (int) $c->modelo,
                'y' => (float) $c->valor
            ])->toArray();

            // El modelo que quiero consultar está en el Ingreso, no en el Avaluo
            $modeloConsultar = (int) $ingreso->modelo;

            // Calcular fórmula y valor estimado para ese modelo
            $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);
 
           
            if($avaluo->formato=='Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota"){
                $pdf = Pdf::loadView('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }else{
                $pdf = Pdf::loadView('pdf.avaluojans',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }

           
        }else{
            if($avaluo->formato=='Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota"){
                $graficaPath = null;
                $resultado = null;
                $pdf = Pdf::loadView('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }else{
                $pdf = Pdf::loadView('pdf.avaluo',compact('ingreso','avaluo','user'));
            }
        }
       

        

        

        // Nombre del archivo
        if($avaluo->formato=='Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota"){
            $nombreArchivo = $ingreso->placa.'-' . $avaluo->inicial.''.$avaluo->consecutivo . '.pdf';
        }else{
            $nombreArchivo = 'avaluo_' . $avaluo->id . '.pdf';
        }
        

        // Ruta absoluta hacia public/
        //$ruta = public_path('documentos/' . $nombreArchivo);


        // Guardar el archivo directamente en public/
        //file_put_contents($ruta, $pdf->output());

        $avaluoupdate=Avaluo::find($avaluo->id);
        $avaluoupdate->file=$nombreArchivo;
        $avaluoupdate->save();

        return response()->json($avaluo);
    }

    public function actualizarCierre(Request $request, Avaluo $avaluo)
    {
        $data = $request->validate([
            'cerrado' => 'required|boolean',
        ]);

        $avaluo->cerrado = $data['cerrado'];
        $avaluo->save();

        return response()->json([
            'message' => $avaluo->cerrado ? 'Avalúo cerrado correctamente.' : 'Avalúo abierto correctamente.',
            'cerrado' => (bool) $avaluo->cerrado,
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

    private function asignarConsecutivoEInicialSiAplica(Avaluo $avaluo): void
    {
        if (empty($avaluo->evaluador) || !empty($avaluo->consecutivo)) {
            return;
        }

        $ultimoAvaluo = Avaluo::where('evaluador', $avaluo->evaluador)
            ->orderBy('consecutivo', 'desc')
            ->first();

        $palabras = preg_split('/\s+/', trim((string) $avaluo->evaluador)) ?: [];
        $inicial = collect($palabras)
            ->filter()
            ->map(fn ($palabra) => strtoupper(substr($palabra, 0, 1)))
            ->join('');

        $avaluo->consecutivo = $ultimoAvaluo ? $ultimoAvaluo->consecutivo + 1 : 1;
        $avaluo->inicial = $inicial;
        $avaluo->save();
    }

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
        $avaluo=Avaluo::find(12);        

        $ingreso = Ingreso::with('avaluo','images')->find($avaluo->ingreso_id);
        $user = User::find(1);
        if($avaluo->tipo=='comercial'){
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            // Dataset corregidos desde el Avaluo
            $corregidos = collect($ingreso->avaluo->corregidos)->map(fn($c) => [
                'x' => (int) $c->modelo,
                'y' => (float) $c->valor
            ])->toArray();

            // El modelo que quiero consultar está en el Ingreso, no en el Avaluo
            $modeloConsultar = (int) $ingreso->modelo;

            // Calcular fórmula y valor estimado para ese modelo
            $resultado = $this->calcularExponencial($corregidos, $modeloConsultar); 
             return view('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user')); 
           
        }else{
            if($avaluo->formato=='Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota"){
                $graficaPath = null;
                $resultado = null;
                return view('pdf.avaluosecbogota',compact('ingreso','avaluo','graficaPath','resultado','user'));
            }
        }

        
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


/**
 * Reprocesar avalúos Sec Bogota con archivos existentes
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function reprocesarSecBogota(Request $request)
{
    // Obtener todos los avalúos con formato "Sec Bogota" y que tengan archivo no nulo/no vacío
    $avaluos = Avaluo::whereHas('ingreso', function($query) {
        $query->where('tiposervicio', 'Sec Bogota');
    })
    ->whereNotNull('file')
    ->where('file', '!=', '')
    ->with(['ingreso', 'clasificados', 'corregidos', 'limitaciones'])
    ->get();

    if ($avaluos->isEmpty()) {
        return response()->json([
            'message' => 'No se encontraron avalúos Sec Bogota con archivos para reprocesar',
            'count' => 0
        ], 200);
    }

    $reprocesados = [];
    $errores = [];

    foreach ($avaluos as $avaluo) {
        try {
            // Simular un request para el reprocesamiento
            $requestSimulado = new Request([
                'id' => $avaluo->ingreso_id,
                'tiposervicio' => 'Sec Bogota',
                'marca' => $avaluo->ingreso->marca ?? null,
                'linea' => $avaluo->ingreso->linea ?? null,
                'fecha_matricula' => $avaluo->ingreso->fecha_matricula ?? null,
                'clase' => $avaluo->ingreso->clase ?? null,
                'tipo_carroceria' => $avaluo->ingreso->tipo_carroceria ?? null,
                'color' => $avaluo->ingreso->color ?? null,
                'cilindraje' => $avaluo->ingreso->cilindraje ?? null,
                'modelo' => $avaluo->ingreso->modelo ?? null,
                'kilometraje' => $avaluo->ingreso->kilometraje ?? null,
                'caja_cambios' => $avaluo->ingreso->caja_cambios ?? null,
                'numero_chasis' => $avaluo->ingreso->numero_chasis ?? null,
                'numero_serie' => $avaluo->ingreso->numero_serie ?? null,
                'numero_motor' => $avaluo->ingreso->numero_motor ?? null,
                'numeroVin' => $avaluo->ingreso->numeroVin ?? null,
                'tipo_servicio_vehiculo' => $avaluo->ingreso->tipo_servicio_vehiculo ?? null,
                'cantidad_ejes' => $avaluo->ingreso->cantidad_ejes ?? null,
                'peso_bruto' => $avaluo->ingreso->peso_bruto ?? null,
                'peso_mermado' => $avaluo->ingreso->peso_mermado ?? null,
                'numero_pasajeros' => $avaluo->ingreso->numero_pasajeros ?? null,
                'estado_registro_runt' => $avaluo->ingreso->estado_registro_runt ?? null,
                'capacidad_ton' => $avaluo->ingreso->capacidad_ton ?? null,
                'fecha_inspeccion' => $avaluo->ingreso->fecha_inspeccion ?? null,
                'fecha_solicitud' => $avaluo->ingreso->fecha_solicitud ?? null,
                'avaluo' => [
                    'clasificados' => $avaluo->clasificados->toArray(),
                    'corregidos' => $avaluo->corregidos->toArray(),
                    'limitaciones' => $avaluo->limitaciones->toArray(),
                    'ingreso_id' => $avaluo->ingreso_id,
                    'vida_util_probable' => $avaluo->vida_util_probable,
                    'vida_usada_dias' => $avaluo->vida_usada_dias,
                    'vida_usada_meses' => $avaluo->vida_usada_meses,
                    'vida_usada_anos' => $avaluo->vida_usada_anos,
                    'vida_util_remate' => $avaluo->vida_util_remate,
                    'vida_util_anos' => $avaluo->vida_util_anos,
                    'antiguedad' => $avaluo->antiguedad,
                    'vida_util' => $avaluo->vida_util,
                    'valor_reposicion' => $avaluo->valor_reposicion,
                    'valor_residual' => $avaluo->valor_residual,
                    'estado_conservacion' => $avaluo->estado_conservacion,
                    'x' => $avaluo->x,
                    'k' => $avaluo->k,
                    'valor_resonable' => $avaluo->valor_resonable,
                    'capacidad_transportadora' => $avaluo->capacidad_transportadora,
                    'valor_razonable' => $avaluo->valor_razonable,
                    'valor_carroceria' => $avaluo->valor_carroceria,
                    'valor_reparaciones' => $avaluo->valor_reparaciones,
                    'valor_llantas' => $avaluo->valor_llantas,
                    'valor_pintura' => $avaluo->valor_pintura,
                    'valor_overhaul_motor' => $avaluo->valor_overhaul_motor,
                    'factor_demerito' => $avaluo->factor_demerito,
                    'valor_accesorios' => $avaluo->valor_accesorios,
                    'indice_responsabilidad_minimo' => $avaluo->indice_responsabilidad_minimo,
                    'avaluo_total' => $avaluo->avaluo_total,
                    'no_factura' => $avaluo->no_factura,
                    'declaracion_importacion' => $avaluo->declaracion_importacion,
                    'fecha_importacion' => $avaluo->fecha_importacion,
                    'registro_maquinaria' => $avaluo->registro_maquinaria,
                    'gps' => $avaluo->gps,
                    'llanta_delantera_izquierda' => $avaluo->llanta_delantera_izquierda,
                    'llanta_delantera_derecha' => $avaluo->llanta_delantera_derecha,
                    'llanta_trasera_izquierda' => $avaluo->llanta_trasera_izquierda,
                    'llanta_trasera_derecha' => $avaluo->llanta_trasera_derecha,
                    'llanta_repuesto' => $avaluo->llanta_repuesto,
                    'tipo' => $avaluo->tipo,
                    'formato' => $avaluo->formato,
                    'observaciones' => $avaluo->observaciones,
                    'latoneria_estado' => $avaluo->latoneria_estado,
                    'latoneria_valor' => $avaluo->latoneria_valor,
                    'pintura_estado' => $avaluo->pintura_estado,
                    'tapiceria_estado' => $avaluo->tapiceria_estado,
                    'tapiceria_valor' => $avaluo->tapiceria_valor,
                    'motor_estado' => $avaluo->motor_estado,
                    'motor_valor' => $avaluo->motor_valor,
                    'chasis_estado' => $avaluo->chasis_estado,
                    'chasis_valor' => $avaluo->chasis_valor,
                    'transmision_estado' => $avaluo->transmision_estado,
                    'transmision_valor' => $avaluo->transmision_valor,
                    'frenos_estado' => $avaluo->frenos_estado,
                    'frenos_valor' => $avaluo->frenos_valor,
                    'refrigeracion_estado' => $avaluo->refrigeracion_estado,
                    'refrigeracion_valor' => $avaluo->refrigeracion_valor,
                    'electrico_estado' => $avaluo->electrico_estado,
                    'electrico_valor' => $avaluo->electrico_valor,
                    'tanque_estado' => $avaluo->tanque_estado,
                    'tanque_valor' => $avaluo->tanque_valor,
                    'bateria_estado' => $avaluo->bateria_estado,
                    'bateria_valor' => $avaluo->bateria_valor,
                    'llantas_estado' => $avaluo->llantas_estado,
                    'llantas_valor' => $avaluo->llantas_valor,
                    'llave_estado' => $avaluo->llave_estado,
                    'llave_valor' => $avaluo->llave_valor,
                    'vidrios_estado' => $avaluo->vidrios_estado,
                    'vidrios_valor' => $avaluo->vidrios_valor,
                    'chatarra' => $avaluo->chatarra,
                    'valor_chatarra_kg' => $avaluo->valor_chatarra_kg,
                    'peso_chatarra_kg' => $avaluo->peso_chatarra_kg,
                    'valor_RTM' => $avaluo->valor_RTM,
                    'valor_SOAT' => $avaluo->valor_SOAT,
                    'valor_faltantes' => $avaluo->valor_faltantes,
                    'codigo_fasecolda' => $avaluo->codigo_fasecolda,
                    'porc_reposicion' => $avaluo->porc_reposicion,
                    'ubicacion' => $avaluo->ubicacion,
                    'evaluador' => $avaluo->evaluador,
                    'code_movilidad' => $avaluo->code_movilidad,
                    'consecutivo' => $avaluo->consecutivo,
                    'inicial' => $avaluo->inicial,
                ]
            ]);

            // Ejecutar el método update con el request simulado
            $this->update($requestSimulado, $avaluo);

            $reprocesados[] = [
                'id' => $avaluo->id,
                'ingreso_id' => $avaluo->ingreso_id,
                'placa' => $avaluo->ingreso->placa,
                'archivo_anterior' => $avaluo->file,
                'archivo_nuevo' => $avaluo->fresh()->file,
                'fecha_reproceso' => now()->toDateTimeString()
            ];

        } catch (\Exception $e) {
            $errores[] = [
                'id' => $avaluo->id,
                'ingreso_id' => $avaluo->ingreso_id,
                'placa' => $avaluo->ingreso->placa ?? 'N/A',
                'error' => $e->getMessage(),
                'fecha_error' => now()->toDateTimeString()
            ];
        }
    }

    return response()->json([
        'message' => 'Proceso de reprocesamiento completado',
        'total_avaluos' => $avaluos->count(),
        'reprocesados_exitosos' => count($reprocesados),
        'errores' => count($errores),
        'detalle_reprocesados' => $reprocesados,
        'detalle_errores' => $errores
    ], 200);
}

/**
 * Reprocesar un avalúo específico por ID
 * 
 * @param int $id
 * @return \Illuminate\Http\JsonResponse
 */
public function reprocesarIndividual($id)
{
    try {
        $avaluo = Avaluo::with(['ingreso', 'clasificados', 'corregidos', 'limitaciones'])
            ->where('id', $id)
            ->first();

        if (!$avaluo) {
            return response()->json(['message' => 'Avalúo no encontrado'], 404);
        }

        // Verificar que sea Sec Bogota y tenga archivo
        if ($avaluo->ingreso->tiposervicio !== 'Sec Bogota') {
            return response()->json(['message' => 'El avalúo no es de tipo Sec Bogota'], 400);
        }

        if (empty($avaluo->file)) {
            return response()->json(['message' => 'El avalúo no tiene archivo para reprocesar'], 400);
        }

        // Simular request para el reprocesamiento
        $requestSimulado = new Request([
            'id' => $avaluo->ingreso_id,
            'tiposervicio' => 'Sec Bogota',
            'marca' => $avaluo->ingreso->marca ?? null,
            'linea' => $avaluo->ingreso->linea ?? null,
            'fecha_matricula' => $avaluo->ingreso->fecha_matricula ?? null,
            'clase' => $avaluo->ingreso->clase ?? null,
            'tipo_carroceria' => $avaluo->ingreso->tipo_carroceria ?? null,
            'color' => $avaluo->ingreso->color ?? null,
            'cilindraje' => $avaluo->ingreso->cilindraje ?? null,
            'modelo' => $avaluo->ingreso->modelo ?? null,
            'kilometraje' => $avaluo->ingreso->kilometraje ?? null,
            'caja_cambios' => $avaluo->ingreso->caja_cambios ?? null,
            'numero_chasis' => $avaluo->ingreso->numero_chasis ?? null,
            'numero_serie' => $avaluo->ingreso->numero_serie ?? null,
            'numero_motor' => $avaluo->ingreso->numero_motor ?? null,
            'numeroVin' => $avaluo->ingreso->numeroVin ?? null,
            'tipo_servicio_vehiculo' => $avaluo->ingreso->tipo_servicio_vehiculo ?? null,
            'cantidad_ejes' => $avaluo->ingreso->cantidad_ejes ?? null,
            'peso_bruto' => $avaluo->ingreso->peso_bruto ?? null,
            'peso_mermado' => $avaluo->ingreso->peso_mermado ?? null,
            'numero_pasajeros' => $avaluo->ingreso->numero_pasajeros ?? null,
            'estado_registro_runt' => $avaluo->ingreso->estado_registro_runt ?? null,
            'capacidad_ton' => $avaluo->ingreso->capacidad_ton ?? null,
            'fecha_inspeccion' => $avaluo->ingreso->fecha_inspeccion ?? null,
            'fecha_solicitud' => $avaluo->ingreso->fecha_solicitud ?? null,
            'avaluo' => [
                'clasificados' => $avaluo->clasificados->toArray(),
                'corregidos' => $avaluo->corregidos->toArray(),
                'limitaciones' => $avaluo->limitaciones->toArray(),
                'ingreso_id' => $avaluo->ingreso_id,
                // ... resto de campos del avalúo (similares al método anterior)
                'vida_util_probable' => $avaluo->vida_util_probable,
                'valor_reposicion' => $avaluo->valor_reposicion,
                // ... agrega todos los campos necesarios
            ]
        ]);

        // Ejecutar el método update
        $this->update($requestSimulado, $avaluo);

        $avaluoActualizado = $avaluo->fresh();

        return response()->json([
            'message' => 'Avalúo reprocesado exitosamente',
            'avaluo_id' => $avaluo->id,
            'ingreso_id' => $avaluo->ingreso_id,
            'placa' => $avaluo->ingreso->placa,
            'archivo_anterior' => $avaluo->file,
            'archivo_nuevo' => $avaluoActualizado->file,
            'fecha_reproceso' => now()->toDateTimeString()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al reprocesar el avalúo',
            'error' => $e->getMessage()
        ], 500);
    }
}


/**
 * Generar PDF para un avalúo específico
 * 
 * @param int $id
 * @return \Illuminate\Http\Response
 */
    public function generarPdf($id, Request $request)
    {
    
    try {
        $avaluo = Avaluo::with(['ingreso', 'clasificados', 'corregidos', 'limitaciones'])->find($id);
        
        if (!$avaluo) {
            return response()->json(['message' => 'Avalúo no encontrado'], 404);
        }
        
        $ingreso = $avaluo->ingreso;
        $user = User::find($avaluo->user_id);
        
        
        // Verificar el tipo de avalúo para determinar qué vista usar
        if ($avaluo->tipo == 'comercial') {
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            
            // Dataset corregidos desde el Avaluo
            $corregidos = collect($avaluo->corregidos)->map(fn($c) => [
                'x' => (int) $c->modelo,
                'y' => (float) $c->valor
            ])->toArray();
            
            // El modelo que quiero consultar está en el Ingreso
            $modeloConsultar = (int) $ingreso->modelo;
            
            // Calcular fórmula y valor estimado
            $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);
            
            if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
                $pdf = Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
            } else {
                $pdf = Pdf::loadView('pdf.avaluojans', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
            }
        } else {
            if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
                $graficaPath = null;
                $resultado = null;
                $pdf = Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
            } else {
                $pdf = Pdf::loadView('pdf.avaluo', compact('ingreso', 'avaluo', 'user'));
            }
        }
        
        // Generar nombre del archivo
        $nombreArchivo = $ingreso->placa . '-' . $avaluo->inicial . $avaluo->consecutivo . '.pdf';
        
        // Si quieres actualizar el archivo en la base de datos (opcional)
        $avaluo->file = $nombreArchivo;
        $avaluo->save();
        
        // Determinar acción: ver o descargar
        $action = $request->query('action', 'view');
        
        if ($action === 'download') {
            // Para descargar: usar attachment
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
                ->header('Access-Control-Expose-Headers', 'Content-Disposition');
        } else {
            // Para ver en el navegador: usar inline
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
        }
        
    } catch (\Exception $e) {        
        return response()->json([
            'message' => 'Error al generar el PDF',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function bulkUpdateCompact(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'array',
            'ids.*' => 'integer|exists:ingresos,id',
            'filtro' => 'nullable|string',
            'all_filtered' => 'nullable|boolean',
            'generar_zip' => 'nullable|boolean',
            'tipo_servicio' => 'nullable|string|in:Sec Bogota,Avaluo',
            'changes' => 'required|array|min:1',
            'changes.codigo_fasecolda' => 'nullable|string',
            'changes.valor_chatarra_kg' => 'nullable|numeric',
            'changes.ubicacion' => 'nullable|string',
            'changes.evaluador' => 'nullable|string',
            'changes.tipo' => 'nullable|string',
            'changes.chatarra' => 'nullable|string|in:Si,No',
            'changes.peso_chatarra_kg' => 'nullable|numeric',
            'changes.observaciones' => 'nullable|string',
            'changes.cilindraje' => 'nullable|integer|min:0',
            'changes.fecha_inspeccion' => 'nullable|date',
            'changes.tipo_vehiculo' => 'nullable|string',
            'changes.es_repuesto_especial' => 'nullable|boolean',
        ]);

        $changes = collect($validated['changes'])
            ->only(self::CAMPOS_MASIVOS_PERMITIDOS)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->toArray();

        if (empty($changes)) {
            return response()->json(['message' => 'No se recibieron cambios válidos para aplicar'], 422);
        }

        $allFiltered = (bool) ($validated['all_filtered'] ?? false);
        $generarZip = (bool) ($validated['generar_zip'] ?? true);
        $tipoServicio = $validated['tipo_servicio'] ?? 'Sec Bogota';
        $ids = $validated['ids'] ?? [];
        $filtro = trim((string) ($validated['filtro'] ?? ''));

        $query = Ingreso::query()
            ->where('tiposervicio', $tipoServicio)
            ->with(['avaluo', 'avaluo.clasificados', 'avaluo.corregidos', 'avaluo.limitaciones']);

        if ($allFiltered) {
            if ($filtro !== '') {
                $query->where(function ($q) use ($filtro) {
                    $q->where('placa', 'like', '%' . $filtro . '%')
                        ->orWhere('solicitante', 'like', '%' . $filtro . '%')
                        ->orWhere('documento_solicitante', 'like', '%' . $filtro . '%')
                        ->orWhere('ubicacion_activo', 'like', '%' . $filtro . '%')
                        ->orWhereHas('avaluo', function ($subQuery) use ($filtro) {
                            $subQuery->where('evaluador', 'like', '%' . $filtro . '%');
                        });
                });
            }
        } else {
            if (empty($ids)) {
                return response()->json(['message' => 'Debes enviar al menos un registro seleccionado'], 422);
            }
            $query->whereIn('id', $ids);
        }

        $ingresos = $query->get();
        if ($ingresos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron registros para edición masiva'], 404);
        }

        $zipFileName = 'avaluos-edicion-masiva-' . now()->format('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        $zip = null;
        if ($generarZip) {
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return response()->json(['message' => 'No fue posible crear el archivo ZIP de salida'], 500);
            }
        }

        $procesados = 0;
        $errores = [];

        foreach ($ingresos as $ingreso) {
            try {
                $avaluo = $ingreso->avaluo;
                if (!$avaluo) {
                    $errores[] = ['ingreso_id' => $ingreso->id, 'error' => 'El ingreso no tiene avalúo asociado'];
                    continue;
                }

                $changesToApply = $changes;

                if (!empty($changesToApply['codigo_fasecolda'])) {
                    $this->sincronizarMemoriasFasecolda($avaluo, $changesToApply['codigo_fasecolda']);

                    $fasecoldaPesoVacio = FasecoldaValor::where('codigo_fasecolda', $changesToApply['codigo_fasecolda'])
                        ->value('peso_vacio');

                    if (!empty($fasecoldaPesoVacio)) {
                        $changesToApply['peso_chatarra_kg'] = $fasecoldaPesoVacio;
                        if (empty($ingreso->peso_bruto)) {
                            $ingreso->update(['peso_bruto' => $fasecoldaPesoVacio]);
                        }
                    }

                    $fasecoldaRow = FasecoldaValor::where('codigo_fasecolda', $changesToApply['codigo_fasecolda'])
                        ->whereIn('tipo', ['corregido', 'clasificado'])
                        ->where('modelo', $ingreso->modelo)
                        ->orderByRaw("FIELD(tipo, 'corregido', 'clasificado')")
                        ->first();

                    if ($fasecoldaRow) {
                        $changesToApply['valor_razonable'] = $fasecoldaRow->valor;
                        $changesToApply['valor_resonable'] = $fasecoldaRow->valor;
                    }
                }

                $changesToApply = $this->aplicarEstadosPorDefecto($changesToApply, $avaluo->toArray());

                $cilindrajeOperacion = array_key_exists('cilindraje', $changesToApply)
                    ? (int) $changesToApply['cilindraje']
                    : $ingreso->cilindraje;
                $fechaInspeccionOperacion = $changesToApply['fecha_inspeccion'] ?? $ingreso->fecha_inspeccion;
                $tipoVehiculoOperacion = $changesToApply['tipo_vehiculo'] ?? $ingreso->clase;
                $esRepuestoEspecialOperacion = array_key_exists('es_repuesto_especial', $changesToApply)
                    ? filter_var($changesToApply['es_repuesto_especial'], FILTER_VALIDATE_BOOLEAN)
                    : false;

                unset($changesToApply['tipo_vehiculo']);
                unset($changesToApply['es_repuesto_especial']);
                

                if (!empty($tipoVehiculoOperacion) && !empty($cilindrajeOperacion)) {
                   $repuesto = ValoresRepuesto::whereRaw('TRIM(tipo) = ?', [
                            trim($this->normalizarTipoVehiculoMasivo($tipoVehiculoOperacion))
                        ])
                        ->whereNotNull('cilindraje_from')
                        ->whereNotNull('cilindraje_to')
                        ->whereRaw('CAST(cilindraje_to AS UNSIGNED) <= ?', [(int)$cilindrajeOperacion])
                        ->whereRaw('CAST(cilindraje_from AS UNSIGNED) >= ?', [(int)$cilindrajeOperacion])
                        ->where('especial', (bool)$esRepuestoEspecialOperacion)
                        ->first();
                       

                    if ($repuesto) {
                        $changesToApply = array_merge($changesToApply, [
                            'latoneria_valor' => $repuesto->latoneria,
                            'valor_RTM' => $repuesto->rtm,
                            'valor_SOAT' => $repuesto->soat,
                            'valor_llantas' => $repuesto->llantas,
                            'motor_valor' => $repuesto->motor_mantenimiento,
                            'chasis_valor' => $repuesto->chasis,
                            'frenos_valor' => $repuesto->frenos,
                            'tanque_valor' => $repuesto->tanque_combustible,
                            'bateria_valor' => $repuesto->bateria,
                            'llave_valor' => $repuesto->llave,
                            'electrico_valor' => $repuesto->sis_electrico,
                            'valor_pintura' => $repuesto->pintura,
                            'tapiceria_valor' => $repuesto->tapiceria,
                            'transmision_valor' => $repuesto->kit_arrastre,
                        ]);
                    }
                }

                $requestSimulado = Request::create('/api/avaluo/' . $avaluo->id, 'PUT', [
                    'id' => $ingreso->id,
                    'placa' => $ingreso->placa,
                    'marca' => $ingreso->marca,
                    'linea' => $ingreso->linea,
                    'fecha_matricula' => $ingreso->fecha_matricula,
                    'clase' => $tipoVehiculoOperacion,
                    'tipo_carroceria' => $ingreso->tipo_carroceria,
                    'color' => $ingreso->color,
                    'cilindraje' => $cilindrajeOperacion,
                    'modelo' => $ingreso->modelo,
                    'kilometraje' => $ingreso->kilometraje,
                    'caja_cambios' => $ingreso->caja_cambios,
                    'numero_chasis' => $ingreso->numero_chasis,
                    'numero_serie' => $ingreso->numero_serie,
                    'numero_motor' => $ingreso->numero_motor,
                    'numeroVin' => $ingreso->numeroVin,
                    'tipo_servicio_vehiculo' => $ingreso->tipo_servicio_vehiculo,
                    'cantidad_ejes' => $ingreso->cantidad_ejes,
                    'peso_bruto' => $ingreso->peso_bruto,
                    'peso_mermado' => $ingreso->peso_mermado,
                    'numero_pasajeros' => $ingreso->numero_pasajeros,
                    'estado_registro_runt' => $ingreso->estado_registro_runt,
                    'capacidad_ton' => $ingreso->capacidad_ton,
                    'fecha_inspeccion' => $fechaInspeccionOperacion,
                    'fecha_solicitud' => $ingreso->fecha_solicitud,
                    'avaluo' => array_merge(
                        $avaluo->toArray(),
                        ['ingreso_id' => $ingreso->id],
                        $changesToApply
                    ),
                ]);
                $requestSimulado->setUserResolver(fn () => auth()->user());

                $updateResponse = $this->update($requestSimulado, $avaluo);
                if ($updateResponse instanceof JsonResponse && $updateResponse->getStatusCode() >= 400) {
                    $updateData = $updateResponse->getData(true);
                    $errores[] = [
                        'ingreso_id' => $ingreso->id,
                        'avaluo_id' => $ingreso->avaluo?->id,
                        'error' => $updateData['message'] ?? 'No fue posible aplicar los cambios al avalúo',
                    ];
                    continue;
                }

                if ($generarZip) {
                    $pdfResponse = $this->generarPdf($avaluo->id, new Request(['action' => 'download']));
                    if ($pdfResponse->getStatusCode() >= 400) {
                        $errores[] = [
                            'ingreso_id' => $ingreso->id,
                            'avaluo_id' => $ingreso->avaluo?->id,
                            'error' => 'No fue posible generar el PDF para este registro',
                        ];
                        continue;
                    }
                    $pdfContent = $pdfResponse->getContent();
                    $prefijoNombre = $ingreso->placa ?: 'ingreso-' . $ingreso->id;
                    $consecutivoNombre = ($avaluo->inicial ?? '') . ($avaluo->consecutivo ?? '');
                    $pdfName = $prefijoNombre . ($consecutivoNombre !== '' ? '-' . $consecutivoNombre : '') . '.pdf';
                    $zip->addFromString($pdfName, $pdfContent);
                }
                $procesados++;
            } catch (\Throwable $e) {
                $errores[] = [
                    'ingreso_id' => $ingreso->id,
                    'avaluo_id' => $ingreso->avaluo?->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($generarZip) {
            $zip->close();
        }

        if ($procesados === 0) {
            if ($generarZip) {
                @unlink($zipPath);
            }
            return response()->json([
                'message' => 'No fue posible procesar registros',
                'errores' => $errores,
            ], 500);
        }

        if ($generarZip) {
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return response()->json([
            'message' => 'Edición masiva aplicada correctamente',
            'procesados' => $procesados,
            'errores' => $errores,
        ]);
    }

    public function bulkImportCompact(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'metodo' => 'required|string|in:comercial,jans',
            'ignorar_consecutivo' => 'nullable|boolean',
        ]);

        $sheet = IOFactory::load($validated['file']->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return response()->json(['message' => 'El archivo no contiene filas para procesar'], 422);
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $zipFileName = 'avaluos-compact-importacion-' . now()->format('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'No fue posible crear el archivo ZIP de salida'], 500);
        }

        $procesados = 0;
        $errores = [];

        foreach ($rows as $index => $rawRow) {
            try {
                $row = $this->mapRowByHeaders($rawRow, $headers);
                $placa = $this->normalizePlate($this->value($row, 'placas'));

                if (!$placa) {
                    throw new \RuntimeException('La columna PLACAS es obligatoria');
                }

                DB::transaction(function () use ($row, $placa, &$procesados, $zip, $validated) {
                    $ingresoData = [
                        'tiposervicio' => 'Sec Bogota',
                        'placa' => $placa,
                        'ubicacion_activo' => $this->value($row, 'ubicacion'),
                        'fecha_inspeccion' => $this->parseExcelDate($this->value($row, 'fecha_avaluo')),
                        'fecha_ingreso' => $this->parseExcelDate($this->value($row, 'fecha_ingreso_a_patios')),
                        'organismo_transito' => $this->value($row, 'organismo_de_transito'),
                        'estado_registro_runt' => $this->value($row, 'estado_de_registro_en_runt'),
                        'marca' => $this->value($row, 'marca'),
                        'clase' => $this->value($row, 'clase'),
                        'tipo_servicio_vehiculo' => $this->value($row, 'servicio'),
                        'linea' => $this->value($row, 'linea'),
                        'modelo' => $this->normalizeNumeric($this->value($row, 'modelo')),
                        'color' => $this->value($row, 'color'),
                        'tipo_carroceria' => $this->value($row, 'carroceria'),
                        'cilindraje' => $this->normalizeNumeric($this->value($row, 'cilindraje')),
                        'numero_motor' => $this->value($row, 'motor'),
                        'numero_chasis' => $this->value($row, 'chasis'),
                        'numero_serie' => $this->value($row, 'serie'),
                        'numeroVin' => $this->value($row, 'vin'),
                        'numero_pasajeros' => $this->normalizeNumeric($this->value($row, 'pasajeros')),
                        'capacidad_ton' => $this->normalizeNumeric($this->value($row, 'capacidad_ton')),
                        'cantidad_ejes' => $this->normalizeNumeric($this->value($row, 'ejes')),
                        'caja_cambios' => $this->value($row, 'caja'),
                        'peso_bruto' => $this->normalizeNumeric($this->value($row, 'peso_vacio')),
                        'peso_mermado' => $this->normalizeNumeric($this->value($row, 'peso_mermado')),
                        'estado' => 'En Inspección',
                    ];

                    $ingreso = Ingreso::firstOrNew([
                        'placa' => $placa,
                        'tiposervicio' => 'Sec Bogota',
                    ]);
                    $ingreso->fill($ingresoData);
                    $ingreso->save();

                    $evaluadorNombre = $this->formatPersonName($this->value($row, 'avaluador'));
                    $evaluador = $evaluadorNombre
                        ? User::where('name', 'like', '%' . trim($evaluadorNombre) . '%')->first()
                        : null;

                    $avaluo = Avaluo::firstOrNew(['ingreso_id' => $ingreso->id]);
                    if (!$avaluo->exists) {
                        $ultimo = Avaluo::whereHas('ingreso', fn ($q) => $q->where('tiposervicio', 'Sec Bogota'))
                            ->orderByDesc('code_movilidad')
                            ->first();
                        $avaluo->code_movilidad = $ultimo ? ($ultimo->code_movilidad + 1) : 1;
                    }

                    $ignorarConsecutivo = filter_var($validated['ignorar_consecutivo'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $avaluo->fill([
                        'ingreso_id' => $ingreso->id,
                        'tipo' => $validated['metodo'],
                        'formato' => 'Sec. Movilidad Bogotá',
                        'fecha_inspeccion' => $this->parseExcelDate($this->value($row, 'fecha_avaluo')),
                        'fecha_inmovilizacion' => $this->parseExcelDate($this->value($row, 'fecha_ingreso_a_patios')),
                        'dias_inmovilizacion' => $this->normalizeInteger($this->value($row, 'dias_inmovilizado')),
                        'evaluador' => $evaluadorNombre,
                        'user_id' => $evaluador?->id,
                        'consecutivo' => $ignorarConsecutivo ? null : $this->normalizeInteger($this->value($row, 'consecutivo')),
                        'inicial' => $ignorarConsecutivo ? null : ($avaluo->inicial ?? null),
                        'codigo_fasecolda' => $this->value($row, 'codigo_fasecolda'),
                        'observaciones' => $this->value($row, 'diagnostico'),
                        'valor_razonable' => $this->normalizeNumeric($this->value($row, 'valor_razonable')),
                        'valor_resonable' => $this->normalizeNumeric($this->value($row, 'valor_razonable')),
                        'valor_SOAT' => $this->normalizeNumeric($this->value($row, 'valor_soat')),
                        'valor_RTM' => $this->normalizeNumeric($this->value($row, 'valor_rtm')),
                        'valor_chatarra_kg' => $this->normalizeNumeric($this->value($row, 'precio_chatarra')),
                        'peso_chatarra_kg' => $this->normalizeNumeric($this->value($row, 'peso_mermado')),
                        'avaluo_total' => $this->normalizeNumeric($this->value($row, 'valor_chatarra')),
                        'ubicacion' => $this->value($row, 'ubicacion'),
                        'latoneria_valor' => $this->normalizeNumeric($this->value($row, 'latoneria')),
                        'pintura_valor' => $this->normalizeNumeric($this->value($row, 'pintura')),
                        'tapiceria_valor' => $this->normalizeNumeric($this->value($row, 'tapiceria')),
                        'motor_valor' => $this->normalizeNumeric($this->value($row, 'motor_1')),
                        'chasis_valor' => $this->normalizeNumeric($this->value($row, 'chasis_1')),
                        'transmision_valor' => $this->normalizeNumeric($this->value($row, 'transmision')),
                        'frenos_valor' => $this->normalizeNumeric($this->value($row, 'sistema_freno')),
                        'refrigeracion_valor' => $this->normalizeNumeric($this->value($row, 'refrigeracion')),
                        'electrico_valor' => $this->normalizeNumeric($this->value($row, 'sis_electrico')),
                        'tanque_valor' => $this->normalizeNumeric($this->value($row, 'tanque_combustible')),
                        'bateria_valor' => $this->normalizeNumeric($this->value($row, 'bateria')),
                        'llantas_valor' => $this->normalizeNumeric($this->value($row, 'llantas')),
                        'llave_valor' => $this->normalizeNumeric($this->value($row, 'llaves')),
                        'vidrios_valor' => $this->normalizeNumeric($this->value($row, 'vidrios')),
                        'valor_reparaciones' => $this->normalizeNumeric($this->value($row, 'valor_reparaciones')),
                        'latoneria_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['latoneria_estado'],
                        'pintura_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['pintura_estado'],
                        'tapiceria_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['tapiceria_estado'],
                        'motor_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['motor_estado'],
                        'chasis_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['chasis_estado'],
                        'transmision_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['transmision_estado'],
                        'frenos_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['frenos_estado'],
                        'refrigeracion_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['refrigeracion_estado'],
                        'electrico_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['electrico_estado'],
                        'tanque_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['tanque_estado'],
                        'bateria_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['bateria_estado'],
                        'llantas_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['llantas_estado'],
                        'llave_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['llave_estado'],
                        'vidrios_estado' => self::ESTADOS_COMPONENTES_POR_DEFECTO['vidrios_estado'],
                    ]);
                    $avaluo->save();
                    if ($ignorarConsecutivo) {
                        $this->asignarConsecutivoEInicialSiAplica($avaluo);
                    }

                    if (!empty($avaluo->codigo_fasecolda)) {
                        $this->sincronizarMemoriasFasecolda($avaluo, $avaluo->codigo_fasecolda);
                    }

                    $limitaciones = $this->buildLimitacionesPayload($row);

                    $requestSimulado = Request::create('/api/avaluo/' . $avaluo->id, 'PUT', [
                        'id' => $ingreso->id,
                        'placa' => $ingreso->placa,
                        'marca' => $ingreso->marca,
                        'linea' => $ingreso->linea,
                        'fecha_matricula' => $ingreso->fecha_matricula,
                        'clase' => $ingreso->clase,
                        'tipo_carroceria' => $ingreso->tipo_carroceria,
                        'color' => $ingreso->color,
                        'cilindraje' => $ingreso->cilindraje,
                        'modelo' => $ingreso->modelo,
                        'kilometraje' => $ingreso->kilometraje,
                        'caja_cambios' => $ingreso->caja_cambios,
                        'numero_chasis' => $ingreso->numero_chasis,
                        'numero_serie' => $ingreso->numero_serie,
                        'numero_motor' => $ingreso->numero_motor,
                        'numeroVin' => $ingreso->numeroVin,
                        'tipo_servicio_vehiculo' => $ingreso->tipo_servicio_vehiculo,
                        'cantidad_ejes' => $ingreso->cantidad_ejes,
                        'peso_bruto' => $ingreso->peso_bruto,
                        'peso_mermado' => $ingreso->peso_mermado,
                        'numero_pasajeros' => $ingreso->numero_pasajeros,
                        'estado_registro_runt' => $ingreso->estado_registro_runt,
                        'capacidad_ton' => $ingreso->capacidad_ton,
                        'fecha_inspeccion' => $ingreso->fecha_inspeccion,
                        'fecha_solicitud' => $ingreso->fecha_solicitud,
                        'avaluo' => array_merge(
                            $avaluo->toArray(),
                            ['ingreso_id' => $ingreso->id],
                            ['limitaciones' => $limitaciones]
                        ),
                    ]);
                    $requestSimulado->setUserResolver(fn () => auth()->user());
                    $this->update($requestSimulado, $avaluo);
                    
                    $this->importDrivePhotos($ingreso->id, $this->value($row, 'enlace_fotos'));
        
                    $pdfResponse = $this->generarPdf($avaluo->id, new Request(['action' => 'download']));
                    if ($pdfResponse->getStatusCode() >= 400) {
                        throw new \RuntimeException('No fue posible generar el PDF del avalúo');
                    }

                    $pdfContent = $pdfResponse->getContent();
                    $pdfName = ($placa ?: 'ingreso-' . $ingreso->id) . '.pdf';
                    $zip->addFromString($pdfName, $pdfContent);
                    $procesados++;
                });
            } catch (\Throwable $e) {
                $errores[] = [
                    'fila' => $index + 2,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $zip->close();

        if ($procesados === 0) {
            @unlink($zipPath);
            return response()->json([
                'message' => 'No fue posible procesar filas del archivo',
                'errores' => $errores,
            ], 500);
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function bulkImportCompactImages(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $sheet = IOFactory::load($validated['file']->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return response()->json(['message' => 'El archivo no contiene filas para procesar'], 422);
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $procesados = 0;
        $imagenesGuardadas = 0;
        $errores = [];

        foreach ($rows as $index => $rawRow) {
            try {
                $row = $this->mapRowByHeaders($rawRow, $headers);
                $placa = $this->normalizePlate($this->value($row, 'placas') ?? $this->value($row, 'placa'));
                $enlaceFotos = $this->value($row, 'enlace_fotos')
                    ?? $this->value($row, 'enlace')
                    ?? $this->value($row, 'link_drive');

                if (!$placa) {
                    throw new \RuntimeException('La columna PLACA/PLACAS es obligatoria');
                }

                if (!$enlaceFotos) {
                    throw new \RuntimeException('La columna ENLACE_FOTOS (o ENLACE/LINK_DRIVE) es obligatoria');
                }

                $ingreso = Ingreso::where('placa', $placa)
                    ->where('tiposervicio', 'Sec Bogota')
                    ->first();

                if (!$ingreso || !$ingreso->avaluo) {
                    throw new \RuntimeException("No se encontró un avalúo compact para la placa {$placa}");
                }

                $guardadasFila = $this->importDrivePhotos($ingreso->id, $enlaceFotos);
                $procesados++;
                $imagenesGuardadas += $guardadasFila;
            } catch (\Throwable $e) {
                $errores[] = [
                    'fila' => $index + 2,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($procesados === 0) {
            return response()->json([
                'message' => 'No fue posible procesar filas del archivo',
                'errores' => $errores,
            ], 500);
        }

        return response()->json([
            'message' => 'Importación de imágenes desde Drive completada',
            'filas_procesadas' => $procesados,
            'imagenes_guardadas' => $imagenesGuardadas,
            'errores' => $errores,
        ]);
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $column => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized === '') {
                continue;
            }
            if (in_array($normalized, $headers, true)) {
                $counter = 1;
                while (in_array($normalized . '_' . $counter, $headers, true)) {
                    $counter++;
                }
                $normalized .= '_' . $counter;
            }
            $headers[$column] = $normalized;
        }

        return $headers;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::of($value)
            ->lower()
            ->ascii()
            ->replace("\t", ' ')
            ->replace('/', ' ')
            ->replace('.', ' ')
            ->replace('-', ' ')
            ->replace('ñ', 'n')
            ->squish()
            ->replace(' ', '_')
            ->toString();

        return trim($value, '_');
    }

    private function mapRowByHeaders(array $row, array $headers): array
    {
        $mapped = [];
        foreach ($headers as $column => $key) {
            $mapped[$key] = $row[$column] ?? null;
        }

        return $mapped;
    }

    private function value(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row)) {
            return null;
        }

        $value = $row[$key];
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizePlate(?string $placa): ?string
    {
        if (!$placa) {
            return null;
        }

        return strtoupper(trim($placa));
    }

    private function normalizarTipoVehiculoMasivo(?string $tipoVehiculo): string
    {
        $normalizado = Str::upper(trim(Str::ascii((string) $tipoVehiculo)));

        return match ($normalizado) {
            'MOTOCILIETA', 'MOTOCICLETAS' => 'MOTOCICLETA',
            default => $normalizado,
        };
    }

    private function normalizeNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', (string) $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = preg_match('/,\d{1,2}$/', $normalized)
                ? str_replace(',', '.', $normalized)
                : str_replace(',', '', $normalized);
        } elseif ($lastDot !== false) {
            $normalized = preg_match('/\.\d{1,2}$/', $normalized)
                ? $normalized
                : str_replace('.', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeInteger($value): ?int
    {
        $numeric = $this->normalizeNumeric($value);
        return $numeric === null ? null : (int) round($numeric);
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildLimitacionesPayload(array $row): array
    {
        $limitaciones = [];

        for ($i = 1; $i <= 7; $i++) {
            $texto = $this->value($row, "limitacion_{$i}");
            if (!$texto) {
                continue;
            }

            $limitaciones[] = ['texto' => $texto];
        }

        return $limitaciones;
    }


    private function aplicarEstadosPorDefecto(array $changes, array $base = []): array
    {
        foreach (self::ESTADOS_COMPONENTES_POR_DEFECTO as $campo => $valorPorDefecto) {
            if (
                (!array_key_exists($campo, $changes) || $changes[$campo] === null || $changes[$campo] === '')
                && empty($base[$campo])
            ) {
                $changes[$campo] = $valorPorDefecto;
            }
        }

        return $changes;
    }

    private function sincronizarMemoriasFasecolda(Avaluo $avaluo, string $codigoFasecolda): void
    {
        $codigo = trim($codigoFasecolda);
        if ($codigo === '') {
            return;
        }

        $registros = FasecoldaValor::where('codigo_fasecolda', $codigo)
            ->whereIn('tipo', ['clasificado', 'corregido'])
            ->orderBy('modelo')
            ->get(['tipo', 'modelo', 'valor']);

        if ($registros->isEmpty()) {
            return;
        }

        $avaluo->clasificados()->delete();
        $avaluo->corregidos()->delete();

        foreach ($registros as $registro) {
            $payload = [
                'modelo' => $registro->modelo,
                'valor' => $registro->valor,
            ];

            if ($registro->tipo === 'clasificado') {
                $avaluo->clasificados()->create($payload);
            }

            if ($registro->tipo === 'corregido') {
                $avaluo->corregidos()->create($payload);
            }
        }
    }

    private function formatPersonName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter(fn ($part) => $part !== '')
            ->map(fn ($part) => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'))
            ->implode(' ');
    }

    private function importDrivePhotos(int $avaluoId, ?string $driveLink): int
    {
        if (!$driveLink) {
            /*Log::warning('importDrivePhotos: enlace de Drive vacío.', [
                'avaluo_id' => $avaluoId,
            ]);*/
            return 0;
        }

        $folderId = $this->extractDriveFolderId($driveLink);
        if (!$folderId) {
            /*Log::warning('importDrivePhotos: no se pudo extraer folderId del enlace.', [
                'avaluo_id' => $avaluoId,
                'drive_link' => $driveLink,
            ]);*/
            return 0;
        }

        $apiKey = (string) 'AIzaSyBrOPQPtTW-31_s7WmKfcp9Aadw5hLDJtw';
        if (!$apiKey) {
            /*Log::error('importDrivePhotos: API key de Google Drive vacía.', [
                'avaluo_id' => $avaluoId,
            ]);*/
            return 0;
        }

        $httpClient = Http::withOptions($this->driveHttpOptions());

        $response = $httpClient->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "'{$folderId}' in parents and trashed = false",
            'fields' => 'files(id,name,mimeType)',
            'key' => $apiKey,
            'pageSize' => 100,
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ]);


        if (!$response->successful()) {
            /*Log::error('importDrivePhotos: error listando archivos en Drive.', [
                'avaluo_id' => $avaluoId,
                'folder_id' => $folderId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);*/
            return 0;
        }

        $files = collect($response->json('files', []))
            ->filter(fn ($file) => str_starts_with((string) ($file['mimeType'] ?? ''), 'image/'))
            ->values();

        if ($files->isEmpty()) {
            /*Log::warning('importDrivePhotos: la carpeta no tiene imágenes disponibles.', [
                'avaluo_id' => $avaluoId,
                'folder_id' => $folderId,
                'total_archivos' => count($response->json('files', [])),
            ]);*/
            return 0;
        }

        IngresoImage::where('avaluo_id', $avaluoId)->where('categoria', 'extra')->delete();
        $orden = 1;
        $saved = 0;

        foreach ($files as $file) {
            $download = $httpClient->timeout(30)->get("https://www.googleapis.com/drive/v3/files/{$file['id']}", [
                'alt' => 'media',
                'key' => $apiKey,
                'supportsAllDrives' => true,
            ]);

            if (!$download->successful()) {
                /*Log::warning('importDrivePhotos: no se pudo descargar archivo de Drive.', [
                    'avaluo_id' => $avaluoId,
                    'file_id' => $file['id'] ?? null,
                    'file_name' => $file['name'] ?? null,
                    'status' => $download->status(),
                ]);*/
                continue;
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if (!$extension) {
                $extension = 'jpg';
            }

            $fileName = Str::uuid() . '.' . $extension;
            $path = "avaluos/{$avaluoId}/{$fileName}";
            $absoluteDir = public_path("avaluos/{$avaluoId}");
            if (!File::exists($absoluteDir)) {
                File::makeDirectory($absoluteDir, 0755, true);
            }

            $absolutePath = public_path($path);
            $bytes = file_put_contents($absolutePath, $download->body());
            if ($bytes === false) {
                /*Log::error('importDrivePhotos: error guardando imagen en /public.', [
                    'avaluo_id' => $avaluoId,
                    'file_name' => $fileName,
                    'absolute_path' => $absolutePath,
                ]);*/
                continue;
            }

            IngresoImage::create([
                'avaluo_id' => $avaluoId,
                'categoria' => 'extra',
                'path' => $path,
                'orden' => $orden++,
            ]);
            $saved++;
        }

        /*Log::info('importDrivePhotos: finalizó importación de imágenes.', [
            'avaluo_id' => $avaluoId,
            'folder_id' => $folderId,
            'imagenes_detectadas' => $files->count(),
            'imagenes_guardadas' => $saved,
            'destino' => '/public/avaluos/{avaluo_id}',
        ]);*/
        return $saved;
    }


    private function driveHttpOptions(): array
    {
        $verifySsl = false;

        if ($verifySsl === false) {
            return ['verify' => false];
        }

        $caBundle = (string) config('services.google.drive_ca_bundle', '');
        if ($caBundle !== '' && is_file($caBundle)) {
            return ['verify' => $caBundle];
        }

        return ['verify' => false];
    }

    private function calcularDiasInmovilizacion($fechaInmovilizacion, $fechaInspeccion): ?int
    {
        if (empty($fechaInmovilizacion) || empty($fechaInspeccion)) {
            return null;
        }

        try {
            return Carbon::parse($fechaInmovilizacion)->diffInDays(Carbon::parse($fechaInspeccion));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractDriveFolderId(string $url): ?string
    {
        if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
