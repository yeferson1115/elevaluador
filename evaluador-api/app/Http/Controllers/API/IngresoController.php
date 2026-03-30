<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Models\Avaluo;
use App\Models\User;
use App\Models\IngresoImage;
use App\Models\HistoricoPropietario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\IngresosImport;
use App\Imports\IngresosMovilidadImport;
use Illuminate\Support\Facades\File;

use App\Exports\AvaluosSecBogotaExport;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\GenerateCertificadosZipJob;

class IngresoController extends Controller
{
    // Obtener listado paginado con búsqueda
public function index(Request $request)
{
    
            
    $query = Ingreso::query()
        ->select('ingresos.*');

    // Si el usuario NO es admin, filtrar por user_id en avaluo o inspeccion
    if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('Super Administrador')) {
        $userId = auth()->id();

        $query->where(function (Builder $q) use ($userId) {
            $q->whereHas('avaluo', function (Builder $sub) use ($userId) {
                $sub->where('user_id', $userId);
            })->orWhereHas('inspeccion', function (Builder $sub) use ($userId) {
                $sub->where('user_id', $userId);
            });
        });
    }

    // Filtro principal: tipo de servicio
    if ($request->filled('tipo')) {
        $tipo = $request->tipo;

        if ($tipo === 'Sec Bogota') {
            $query->where('tiposervicio', 'Sec Bogota');
        } else {
            $query->whereIn('tiposervicio', [$tipo, 'Avaluo e Inspección']);
        }

         if ($tipo === 'Inspección') {
            $query->with(['inspeccion']);
        }
        if ($tipo === 'Avaluo' || $tipo == 'Sec Bogota') {
            $query->with(['avaluo']);
        }
    }

    // Filtro secundario: búsqueda por texto
    if ($request->filled('search')) {
        $this->applyCompactSearch($query, $request->input('search'));
    }

    return response()->json(
        $query->orderByDesc('ingresos.id')->paginate(10)
    );
}

private function buildIndexRelations(?string $tipo): array
{
    $relations = [];

    if ($tipo === 'Inspección') {
        $relations['inspeccion'] = function ($query) {
            $query->select('id', 'ingreso_id', 'user_id');
        };
    }

    if ($tipo === 'Avaluo' || $tipo === 'Sec Bogota') {
        $relations['avaluo'] = function ($query) {
            $query->select('id', 'ingreso_id', 'user_id', 'evaluador', 'file');
        };
    }

    return $relations;
}

private function applyCompactSearch(Builder $query, string $search): void
{
    $search = trim($search);

    if ($search == '') {
        return;
    }

    $plateTerms = $this->extractPlateTerms($search);
    $allTermsArePlates = $this->allTermsArePlates($search);

    if ($allTermsArePlates && count($plateTerms) > 1) {
        $query->whereIn(DB::raw('UPPER(placa)'), $plateTerms);
        return;
    }

    $normalizedSearch = mb_strtoupper($search);
    $plateLooksSpecific = preg_match('/^[A-Z0-9-]{5,10}$/', $normalizedSearch) === 1;

    $query->where(function (Builder $q) use ($search, $normalizedSearch, $plateLooksSpecific) {
        if ($plateLooksSpecific) {
            $q->whereRaw('UPPER(placa) LIKE ?', [$normalizedSearch . '%']);
        } else {
            $q->whereRaw('UPPER(placa) LIKE ?', ['%' . $normalizedSearch . '%']);
        }

        $q->orWhere('solicitante', 'like', "%{$search}%")
            ->orWhereRaw('UPPER(marca) LIKE ?', ['%' . $normalizedSearch . '%'])
            ->orWhere('documento_solicitante', 'like', "%{$search}%")
            ->orWhereHas('avaluo', function (Builder $subQuery) use ($search) {
                $subQuery->where('evaluador', 'like', "%{$search}%");
            });
    });
}

private function extractPlateTerms(string $search): array
{
    return collect(preg_split('/[\s,;]+/u', mb_strtoupper($search)) ?: [])
        ->map(fn ($term) => trim($term))
        ->filter(fn ($term) => $term !== '')
        ->filter(fn ($term) => preg_match('/^[A-Z0-9-]{5,10}$/', $term))
        ->unique()
        ->values()
        ->all();
}

private function allTermsArePlates(string $search): bool
{
    $terms = collect(preg_split('/[\s,;]+/u', mb_strtoupper($search)) ?: [])
        ->map(fn ($term) => trim($term))
        ->filter(fn ($term) => $term !== '')
        ->values();

    return $terms->isNotEmpty()
        && $terms->every(fn ($term) => preg_match('/^[A-Z0-9-]{5,10}$/', $term));
}


    // Crear un nuevo avalúo
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $this->validateAvaluo($request);

            // Guardar ingreso
            $avaluo = Ingreso::create($this->mapAvaluoData($data));

            // Guardar histórico de propietarios
            if ($request->has('historicoPropietarios') && is_array($request->historicoPropietarios)) {
                foreach ($request->historicoPropietarios as $propietario) {
                    HistoricoPropietario::create([
                        'ingreso_id' => $avaluo->id,
                        'nombre_empresa' => $propietario['nombre_empresa'] ?? null,
                        'tipo_propietario' => $propietario['tipo_propietario'] ?? null,
                        'tipo_identificacion' => $propietario['tipo_identificacion'] ?? null,
                        'numero_identificacion' => $propietario['numero_identificacion'] ?? null,
                        'fecha_inicio' => $propietario['fecha_inicio'] ?? null,
                        'estado' => $propietario['estado'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Avalúo creado correctamente',
                'data' => $avaluo->load('historicoPropietarios')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el avalúo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener un avalúo específico
    public function show($id)
    {
        $avaluo = Ingreso::with('historicoPropietarios')->find($id);

        if (!$avaluo) {
            return response()->json(['message' => 'Avalúo no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($avaluo);
    }

    // Actualizar un avalúo existente
    public function update(Request $request, $id)
    {
         DB::beginTransaction();

        try {
            $avaluo = Ingreso::find($id);

            if (!$avaluo) {
                return response()->json(['message' => 'Avalúo no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $data = $this->validateAvaluo($request);

            // Actualizar datos del ingreso
            $avaluo->update($this->mapAvaluoData($data));

            // Actualizar histórico de propietarios
            if ($request->has('historicoPropietarios') && is_array($request->historicoPropietarios)) {
                // Borro lo anterior y guardo lo nuevo (opción simple)
                HistoricoPropietario::where('ingreso_id', $avaluo->id)->delete();

                foreach ($request->historicoPropietarios as $propietario) {
                    HistoricoPropietario::create([
                        'ingreso_id' => $avaluo->id,
                        'nombre_empresa' => $propietario['nombre_empresa'] ?? null,
                        'tipo_propietario' => $propietario['tipo_propietario'] ?? null,
                        'tipo_identificacion' => $propietario['tipo_identificacion'] ?? null,
                        'numero_identificacion' => $propietario['numero_identificacion'] ?? null,
                        'fecha_inicio' => $propietario['fecha_inicio'] ?? null,
                        'estado' => $propietario['estado'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Avalúo actualizado correctamente',
                'data' => $avaluo->load('historicoPropietarios')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el avalúo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar un avalúo
     public function destroy($id)
    {
        $ingreso = Ingreso::find($id);
        
        if (!$ingreso) {
            return response()->json(['message' => 'Avalúo no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // 1. Primero buscar el avalúo relacionado para eliminar su archivo PDF
        $avaluo = Avaluo::where('ingreso_id', $id)->first();
        if ($avaluo && $avaluo->file) {
            $pdfPath = public_path('documentos/' . $avaluo->file);
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }
        }

        // 2. Eliminar todas las imágenes relacionadas y sus archivos físicos
        $imagenes = IngresoImage::where('avaluo_id', $id)->get();
        
        foreach ($imagenes as $imagen) {
            // Eliminar archivo físico
            $fullPath = public_path($imagen->path);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
            // Eliminar registro en la base de datos
            $imagen->delete();
        }

        // 3. Eliminar la carpeta del avalúo si existe
        $carpetaAvaluo = public_path("avaluos/{$id}");
        if (File::exists($carpetaAvaluo) && File::isDirectory($carpetaAvaluo)) {
            // Eliminar todos los archivos dentro de la carpeta primero
            File::deleteDirectory($carpetaAvaluo);
        }

        // 4. Eliminar gráfica generada si existe
        $graficaPath = public_path("graficas/avaluo_{$id}.png");
        if (File::exists($graficaPath)) {
            File::delete($graficaPath);
        }

        // 5. Eliminar el avalúo si existe
        if ($avaluo) {
            $avaluo->delete();
        }

        // 6. Finalmente eliminar el ingreso
        $ingreso->delete();

        return response()->json([
            'message' => 'Avalúo eliminado correctamente',
            'carpeta_eliminada' => "avaluos/{$id}"
        ]);
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
            'informacionBien.cilindraje' => 'nullable|numeric',
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
            'informacionBien.documento_propietario' => 'nullable|string',
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
            'informacionBien.cantidad_ejes' => 'nullable|integer',
            'informacionBien.peso_bruto' => 'nullable|numeric',
            'informacionBien.numeroVin' => 'nullable|string',
            
             // 📌 Estado Vehículo RUNT
        'estadoVehiculoRunt.fecha_inicial_matricula' => 'nullable|date',
        'estadoVehiculoRunt.estado_matricula' => 'nullable|string',
        'estadoVehiculoRunt.traslados_matricula' => 'nullable|string',
        'estadoVehiculoRunt.tipo_servicio_vehiculo' => 'nullable|string',
        'estadoVehiculoRunt.cambios_tipo_servicio' => 'nullable|string',
        'estadoVehiculoRunt.fecha_ult_cambio_servicio' => 'nullable|date',
        'estadoVehiculoRunt.cambio_color_historica' => 'nullable|string',
        'estadoVehiculoRunt.fecha_ult_cambio_color' => 'nullable|date',
        'estadoVehiculoRunt.color_cambiado' => 'nullable|string',
        'estadoVehiculoRunt.cambios_blindaje' => 'nullable|string',
        'estadoVehiculoRunt.fecha_cambio_blindaje' => 'nullable|date',
        'estadoVehiculoRunt.repotenciado' => 'nullable|string',

        // 📌 Novedades Vehículo
        'novedadesVehiculo.tiene_gravamedes' => 'nullable|string',
        'novedadesVehiculo.tiene_prenda' => 'nullable|string',
        'novedadesVehiculo.regrabado_no_motor' => 'nullable|string',
        'novedadesVehiculo.regrabado_no_chasis' => 'nullable|string',
        'novedadesVehiculo.regrabado_no_serie' => 'nullable|string',
        'novedadesVehiculo.regrabado_no_vin' => 'nullable|string',
        'novedadesVehiculo.limitacion_propiedad' => 'nullable|string',
        'novedadesVehiculo.numero_doc_proceso' => 'nullable|string',
        'novedadesVehiculo.entidad_juridica' => 'nullable|string',
        'novedadesVehiculo.tipo_doc_demandante' => 'nullable|string',
        'novedadesVehiculo.no_identificacion_demandante' => 'nullable|string',
        'novedadesVehiculo.fecha_expedicion_novedad' => 'nullable|date',
        'novedadesVehiculo.fecha_radicacion' => 'nullable|date',
        ]);
    }

   private function mapAvaluoData(array $data)
{
    return [
        // =====================
        // DATOS GENERALES
        // =====================
        'tiposervicio' => $data['datosGenerales']['tiposervicio'] ?? null,
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
        'estado' => $data['datosGenerales']['estado'] ?? 'En Inspección',

        // =====================
        // INFORMACIÓN DEL BIEN
        // =====================
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
        'numero_chasis' => $data['informacionBien']['numeroChasis'] ?? null,
        'numero_serie' => $data['informacionBien']['numeroSerie'] ?? null,
        'numero_motor' => $data['informacionBien']['numeroMotor'] ?? null,
        'nacionalidad' => $data['informacionBien']['nacionalidad'] ?? null,
        'propietario' => $data['informacionBien']['propietario'] ?? null,
        'documento_propietario' => $data['informacionBien']['documento_propietario'] ?? null,        
        'empresa_afiliacion' => $data['informacionBien']['empresaAfiliacion'] ?? null,
        'ciudad_registro' => $data['informacionBien']['ciudad_registro'] ?? null,
        'no_licencia' => $data['informacionBien']['no_licencia'] ?? null,
        'fecha_expedicion_licencia' => $data['informacionBien']['fecha_expedicion_licencia'] ?? null,
        'organismo_transito' => $data['informacionBien']['organismo_transito'] ?? null,
        'soat' => $data['informacionBien']['soat'] ?? null,
        'fecha_expedicion_soat' => $data['informacionBien']['fecha_expedicion_soat'] ?? null,
        'fecha_inicio_vigencia_soat' => $data['informacionBien']['fecha_inicio_vigencia_soat'] ?? null,
        'fecha_vencimiento_soat' => $data['informacionBien']['fecha_vencimiento_soat'] ?? null,
        'entidad_expide_soat' => $data['informacionBien']['entidad_expide_soat'] ?? null,
        'estado_soat' => $data['informacionBien']['estado_soat'] ?? null,
        'rtm' => $data['informacionBien']['rtm'] ?? null,
        'fecha_vencimiento_rtm' => $data['informacionBien']['fecha_vencimiento_rtm'] ?? null,
        'centro_revision_rtm' => $data['informacionBien']['centro_revision_rtm'] ?? null,
        'estado_rtm' => $data['informacionBien']['estado_rtm'] ?? null,
        'peso_bruto' => $data['informacionBien']['peso_bruto'] ?? null,
        'cantidad_ejes' => $data['informacionBien']['cantidad_ejes'] ?? null,
        'numeroVin' => $data['informacionBien']['numeroVin'] ?? null,
        

        // =====================
        // ESTADO VEHÍCULO RUNT
        // =====================
        'fecha_inicial_matricula' => $data['estadoVehiculoRunt']['fecha_inicial_matricula'] ?? null,
        'estado_matricula' => $data['estadoVehiculoRunt']['estado_matricula'] ?? null,
        'traslados_matricula' => $data['estadoVehiculoRunt']['traslados_matricula'] ?? null,
        'tipo_servicio_vehiculo' => $data['estadoVehiculoRunt']['tipo_servicio_vehiculo'] ?? null,
        'cambios_tipo_servicio' => $data['estadoVehiculoRunt']['cambios_tipo_servicio'] ?? null,
        'fecha_ult_cambio_servicio' => $data['estadoVehiculoRunt']['fecha_ult_cambio_servicio'] ?? null,
        'cambio_color_historica' => $data['estadoVehiculoRunt']['cambio_color_historica'] ?? null,
        'fecha_ult_cambio_color' => $data['estadoVehiculoRunt']['fecha_ult_cambio_color'] ?? null,
        'color_cambiado' => $data['estadoVehiculoRunt']['color_cambiado'] ?? null,
        'cambios_blindaje' => $data['estadoVehiculoRunt']['cambios_blindaje'] ?? null,
        'fecha_cambio_blindaje' => $data['estadoVehiculoRunt']['fecha_cambio_blindaje'] ?? null,
        'repotenciado' => $data['estadoVehiculoRunt']['repotenciado'] ?? null,

        // =====================
        // NOVEDADES VEHÍCULO
        // =====================
        'tiene_gravamedes' => $data['novedadesVehiculo']['tiene_gravamedes'] ?? null,
        'tiene_prenda' => $data['novedadesVehiculo']['tiene_prenda'] ?? null,
        'regrabado_no_motor' => $data['novedadesVehiculo']['regrabado_no_motor'] ?? null,
        'regrabado_no_chasis' => $data['novedadesVehiculo']['regrabado_no_chasis'] ?? null,
        'regrabado_no_serie' => $data['novedadesVehiculo']['regrabado_no_serie'] ?? null,
        'regrabado_no_vin' => $data['novedadesVehiculo']['regrabado_no_vin'] ?? null,
        'limitacion_propiedad' => $data['novedadesVehiculo']['limitacion_propiedad'] ?? null,
        'numero_doc_proceso' => $data['novedadesVehiculo']['numero_doc_proceso'] ?? null,
        'entidad_juridica' => $data['novedadesVehiculo']['entidad_juridica'] ?? null,
        'tipo_doc_demandante' => $data['novedadesVehiculo']['tipo_doc_demandante'] ?? null,
        'no_identificacion_demandante' => $data['novedadesVehiculo']['no_identificacion_demandante'] ?? null,
        'fecha_expedicion_novedad' => $data['novedadesVehiculo']['fecha_expedicion_novedad'] ?? null,
        'fecha_radicacion' => $data['novedadesVehiculo']['fecha_radicacion'] ?? null,
    ];
}

public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,csv,xls'
    ]);

    try {
        Excel::import(new IngresosImport, $request->file('file'));

        return response()->json([
            'message' => 'Archivo importado correctamente'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al importar el archivo',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function importmovilidad(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,csv,xls'
    ]);

    try {
        Excel::import(new IngresosMovilidadImport, $request->file('file'));

        return response()->json([
            'message' => 'Archivo importado correctamente'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al importar el archivo',
            'error' => $e->getMessage()
        ], 500);
    }
}



private function obtenerIdsSeleccionados(Request $request): array
{
    return collect($request->input('ids', []))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();
}

private function aplicarFiltroExportacion($query, string $tiposervicio, ?string $filtro = '', array $ids = [])
{
    $filtro = trim((string) ($filtro ?? ''));
    $normalizedFilter = mb_strtoupper($filtro);
    $plateTerms = $this->extractPlateTerms($filtro);
    $allTermsArePlates = $this->allTermsArePlates($filtro);

    $query->where('tiposervicio', $tiposervicio)
          ->whereHas('avaluo', function($q) {
              $q->whereNotNull('file')
                ->where('file', '!=', '');
          });

    if (!empty($ids)) {
        $query->whereIn('id', $ids);
    }

    if ($filtro) {
        if ($allTermsArePlates && count($plateTerms) > 1) {
            $query->whereIn(DB::raw('UPPER(placa)'), $plateTerms);
            return $query;
        }

        $query->where(function ($q) use ($filtro, $normalizedFilter) {
            $q->whereRaw('UPPER(placa) like ?', ['%' . $normalizedFilter . '%'])
              ->orWhere('solicitante', 'like', '%' . $filtro . '%')
              ->orWhereRaw('UPPER(marca) LIKE ?', ['%' . $normalizedFilter . '%'])
              ->orWhere('ubicacion_activo', 'like', '%' . $filtro . '%')
              ->orWhereHas('avaluo', function ($subQuery) use ($filtro) {
                  $subQuery->where('evaluador', 'like', '%' . $filtro . '%');
              });
        });
    }

    return $query;
}

public function exportSecBog(Request $request)
{
    $filtro = $request->get('filtro', '');
    $tiposervicio = 'Sec Bogota';
    
    return Excel::download(new AvaluosSecBogotaExport($filtro, $tiposervicio), 
        'avaluos-sec-bogota-' . now()->format('Y-m-d') . '.xlsx');
}

public function exportCertificadosZip(Request $request)
{
    \Log::info('=== INICIANDO exportCertificadosZip ===');
    \Log::info('Filtro recibido: ' . $request->get('filtro', ''));
    
    $filtro = $request->get('filtro', '');
    $tiposervicio = 'Sec Bogota';
    $ids = $this->obtenerIdsSeleccionados($request);
    
    // Crear la consulta con el mismo filtro que el Excel
    $query = Ingreso::with([
        'avaluo' => function($query) {
            $query->whereNotNull('file')
                  ->where('file', '!=', '');
        }, 
        'avaluo.clasificados', 
        'avaluo.corregidos', 
        'avaluo.limitaciones', 
        'images'
    ]);

    $this->aplicarFiltroExportacion($query, $tiposervicio, $filtro, $ids);
    
    $ingresos = $query->get();
    
    \Log::info('Total ingresos encontrados: ' . $ingresos->count());
    
    if ($ingresos->isEmpty()) {
        \Log::warning('No hay certificados con archivos PDF para exportar');
        return response()->json([
            'message' => 'No hay certificados con archivos PDF para exportar'
        ], 404);
    }
    
    // Mostrar qué ingresos se encontraron
    foreach ($ingresos as $ingreso) {
        \Log::info('Ingreso encontrado:', [
            'id' => $ingreso->id,
            'placa' => $ingreso->placa,
            'file' => $ingreso->avaluo->file ?? 'N/A',
            'tiene_datos' => $this->validarDatosAvaluo($ingreso)
        ]);
    }
    
    // Crear archivo ZIP temporal
    $zipFileName = 'certificados-sec-bogota-' . now()->format('Y-m-d-H-i') . '.zip';
    $zipPath = storage_path('app/temp/' . $zipFileName);
    
    \Log::info('Creando ZIP en: ' . $zipPath);
    
    // Asegurarse de que exista el directorio temporal
    if (!file_exists(dirname($zipPath))) {
        mkdir(dirname($zipPath), 0755, true);
        \Log::info('Directorio temporal creado: ' . dirname($zipPath));
    }
    
    $zip = new ZipArchive();
    $zipOpenResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    if ($zipOpenResult === TRUE) {
        \Log::info('ZIP abierto correctamente');
        
        $archivosAgregados = 0;
        $errores = [];
        $avaluosSinDatos = [];
        
        foreach ($ingresos as $ingreso) {
            \Log::info('Procesando ingreso: ' . $ingreso->placa);
            
            // Verificar que el avalúo existe y tiene archivo
            if ($ingreso->avaluo && !empty($ingreso->avaluo->file)) {
                try {
                    // Validar datos mínimos para generar PDF
                    if (!$this->validarDatosAvaluo($ingreso)) {
                        \Log::warning('Avaluo sin datos suficientes: ' . $ingreso->placa);
                        $avaluosSinDatos[] = [
                            'placa' => $ingreso->placa,
                            'id' => $ingreso->id,
                            'avaluo_id' => $ingreso->avaluo->id,
                            'razon' => 'Datos insuficientes para generar PDF'
                        ];
                        continue;
                    }
                    
                    \Log::info('Generando PDF para: ' . $ingreso->placa);
                    
                    // Generar el PDF dinámicamente
                    $pdf = $this->generarPdfParaZip($ingreso);
                    
                    if ($pdf) {
                        try {
                            $pdfContent = $pdf->output();
                            
                            if (!empty($pdfContent)) {
                                // Nombre del archivo en el ZIP
                                $fileNameInZip = $this->generarNombreArchivoZip($ingreso);
                                
                                \Log::info('Agregando al ZIP: ' . $fileNameInZip . ' (tamaño: ' . strlen($pdfContent) . ' bytes)');
                                
                                // Agregar contenido PDF al ZIP
                                $addResult = $zip->addFromString($fileNameInZip, $pdfContent);
                                
                                if ($addResult) {
                                    $archivosAgregados++;
                                    \Log::info('Archivo agregado exitosamente al ZIP');
                                } else {
                                    \Log::error('Error al agregar archivo al ZIP');
                                    $errores[] = [
                                        'placa' => $ingreso->placa,
                                        'error' => 'Error al agregar archivo al ZIP'
                                    ];
                                }
                            } else {
                                \Log::error('Contenido PDF vacío para: ' . $ingreso->placa);
                                $errores[] = [
                                    'placa' => $ingreso->placa,
                                    'error' => 'Contenido PDF vacío',
                                    'avaluo_id' => $ingreso->avaluo->id
                                ];
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error al obtener contenido PDF: ' . $e->getMessage());
                            $errores[] = [
                                'placa' => $ingreso->placa,
                                'error' => 'Error al obtener contenido PDF: ' . $e->getMessage(),
                                'avaluo_id' => $ingreso->avaluo->id
                            ];
                        }
                    } else {
                        \Log::error('No se pudo generar el objeto PDF para: ' . $ingreso->placa);
                        $errores[] = [
                            'placa' => $ingreso->placa,
                            'error' => 'No se pudo generar el objeto PDF',
                            'avaluo_id' => $ingreso->avaluo->id
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error('Error procesando ingreso ' . $ingreso->placa . ': ' . $e->getMessage());
                    $errores[] = [
                        'placa' => $ingreso->placa,
                        'archivo' => $ingreso->avaluo->file ?? 'N/A',
                        'error' => $e->getMessage(),
                        'linea' => $e->getLine(),
                        'archivo_error' => $e->getFile(),
                        'trace' => $e->getTraceAsString()
                    ];
                    continue;
                }
            } else {
                \Log::warning('Ingreso sin avaluo o file vacío: ' . $ingreso->placa);
            }
        }
        
        \Log::info('Cerrando ZIP. Archivos agregados: ' . $archivosAgregados);
        $zip->close();
        
        if ($archivosAgregados === 0) {
            \Log::error('No se pudieron generar archivos PDF para exportar');
            return response()->json([
                'message' => 'No se pudieron generar archivos PDF para exportar',
                'total_avaluos' => $ingresos->count(),
                'archivos_generados' => $archivosAgregados,
                'errores' => $errores,
                'avaluos_sin_datos' => $avaluosSinDatos
            ], 404);
        }
        
        \Log::info('=== ZIP CREADO EXITOSAMENTE ===');
        \Log::info('Archivo: ' . $zipPath);
        \Log::info('Tamaño: ' . (file_exists($zipPath) ? filesize($zipPath) : 0) . ' bytes');
        
        // Verificar que el archivo existe antes de descargar
        if (!file_exists($zipPath)) {
            \Log::error('El archivo ZIP no se creó en: ' . $zipPath);
            return response()->json([
                'message' => 'Error: El archivo ZIP no se creó'
            ], 500);
        }
        
        // Devolver el archivo ZIP
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        
    } else {
        \Log::error('Error al abrir ZIP. Código: ' . $zipOpenResult);
        return response()->json([
            'message' => 'Error al crear el archivo ZIP. Código: ' . $zipOpenResult
        ], 500);
    }
}

/**
 * Validar que el avalúo tiene datos mínimos para generar PDF
 */
private function validarDatosAvaluo(Ingreso $ingreso): bool
{
    if (!$ingreso->avaluo) {
        return false;
    }
    
    // Verificar datos mínimos requeridos
    $camposRequeridos = [
        'placa' => $ingreso->placa,
        'marca' => $ingreso->marca,
        'modelo' => $ingreso->modelo,
        'valor_razonable' => $ingreso->avaluo->valor_razonable,
    ];
    
    foreach ($camposRequeridos as $campo => $valor) {
        if (empty($valor) && $valor !== 0 && $valor !== '0') {
           
            return false;
        }
    }
    
    return true;
}

/**
 * Generar nombre consistente para archivos en el ZIP
 */
private function generarNombreArchivoZip(Ingreso $ingreso): string
{
    $placa = $ingreso->placa ?? 'sin_placa';
    $inicial = $ingreso->avaluo->inicial ?? '';
    $consecutivo = $ingreso->avaluo->consecutivo ?? '';
    
    // Limpiar placa de caracteres no válidos para nombres de archivo
    $placaLimpia = preg_replace('/[^a-zA-Z0-9]/', '_', $placa);
    
    if (!empty($inicial) && !empty($consecutivo)) {
        return "{$placaLimpia}-{$inicial}{$consecutivo}.pdf";
    }
    
    // Si no tiene inicial y consecutivo, usar el nombre del archivo guardado
    if (!empty($ingreso->avaluo->file)) {
        $nombreArchivo = basename($ingreso->avaluo->file);
        // Asegurar que tenga extensión .pdf
        if (!str_ends_with(strtolower($nombreArchivo), '.pdf')) {
            $nombreArchivo .= '.pdf';
        }
        return "{$placaLimpia}_{$nombreArchivo}";
    }
    
    return "{$placaLimpia}.pdf";
}

/**
 * Método para generar PDF optimizado para ZIP
 */
/**
 * Método para generar PDF optimizado para ZIP
 */
private function generarPdfParaZip(Ingreso $ingreso)
{
    try {
        \Log::info('=== INICIANDO generarPdfParaZip ===');
        \Log::info('Ingreso ID: ' . $ingreso->id . ', Placa: ' . $ingreso->placa);
        
        $avaluo = $ingreso->avaluo;
        
        if (!$avaluo) {
            \Log::error('No hay objeto avaluo', ['ingreso_id' => $ingreso->id]);
            return null;
        }
        
        \Log::info('Avaluo encontrado, ID: ' . $avaluo->id);
        \Log::info('Tipo avaluo: ' . $avaluo->tipo);
        \Log::info('Formato avaluo: ' . $avaluo->formato);
        
        $user = \App\Models\User::find($avaluo->user_id);
        \Log::info('User encontrado: ' . ($user ? 'Sí' : 'No'));
        
        // Determinar qué vista usar basado en el tipo
        if (in_array($avaluo->tipo, ['comercial', 'jans'])) {
            \Log::info('Usando vista para tipo: ' . $avaluo->tipo);
            
            // Para exportación masiva, omitimos la gráfica para mejor performance
            $graficaPath = $this->generarGraficaDispercion($avaluo);
            $resultado = null;
            
            // Solo calcular resultado si hay corregidos
            if ($avaluo->corregidos && $avaluo->corregidos->isNotEmpty()) {
                \Log::info('Calculando resultado con corregidos');
                $corregidos = collect($avaluo->corregidos)->map(fn($c) => [
                    'x' => (int) $c->modelo,
                    'y' => (float) $c->valor
                ])->toArray();
                
                $modeloConsultar = (int) $ingreso->modelo;
                $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);
            } else {
                \Log::info('No hay corregidos para calcular');
            }
            
            if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
                \Log::info('Usando vista: pdf.avaluosecbogota');
                try {
                    $pdf = Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
                    \Log::info('PDF creado exitosamente');
                    return $pdf;
                } catch (\Exception $e) {
                    \Log::error('Error cargando vista avaluosecbogota: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                \Log::info('Usando vista: pdf.avaluojans');
                try {
                    $pdf = Pdf::loadView('pdf.avaluojans', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
                    \Log::info('PDF creado exitosamente');
                    return $pdf;
                } catch (\Exception $e) {
                    \Log::error('Error cargando vista avaluojans: ' . $e->getMessage());
                    throw $e;
                }
            }
        } else {
            \Log::info('Usando vista estándar, tipo: ' . ($avaluo->tipo ?? 'N/A'));
            
            if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
                \Log::info('Usando vista: pdf.avaluosecbogota (estándar)');
                $graficaPath = null;
                $resultado = null;
                try {
                    $pdf = Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
                    \Log::info('PDF creado exitosamente');
                    return $pdf;
                } catch (\Exception $e) {
                    \Log::error('Error cargando vista avaluosecbogota estándar: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                \Log::info('Usando vista: pdf.avaluo');
                try {
                    $pdf = Pdf::loadView('pdf.avaluo', compact('ingreso', 'avaluo', 'user'));
                    \Log::info('PDF creado exitosamente');
                    return $pdf;
                } catch (\Exception $e) {
                    \Log::error('Error cargando vista avaluo: ' . $e->getMessage());
                    throw $e;
                }
            }
        }
        
    } catch (\Exception $e) {
        \Log::error('Error en generarPdfParaZip: ' . $e->getMessage());
        \Log::error('Trace: ' . $e->getTraceAsString());
        
        // Intentar con una vista más simple si falla
        \Log::info('Intentando con vista simple de respaldo');
        try {
            $pdf = Pdf::loadView('pdf.simple', [
                'placa' => $ingreso->placa,
                'avaluo_id' => $avaluo->id ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            return $pdf;
        } catch (\Exception $e2) {
            \Log::error('Error incluso con vista simple: ' . $e2->getMessage());
            return null;
        }
    }
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

/**
 * Exportación optimizada para grandes volúmenes usando tus vistas existentes
 */
public function exportCertificadosZipOptimizado(Request $request)
{
    \Log::info('=== EXPORTACIÓN OPTIMIZADA PARA GRANDES VOLÚMENES ===');
    
    // ✅ Aumentar límites para procesamiento masivo
    ini_set('max_execution_time', 3600); // 1 hora
    ini_set('memory_limit', '2048M');    // 2GB
    
    $filtro = $request->get('filtro', '');
    $tiposervicio = 'Sec Bogota';
    $ids = $this->obtenerIdsSeleccionados($request);
    
    // ✅ Solo obtener IDs primero para estimar
    $query = Ingreso::query();
    $this->aplicarFiltroExportacion($query, $tiposervicio, $filtro, $ids);
    
    $total = $query->count();
    
    \Log::info("Total registros a procesar: {$total}");
    
    if ($total === 0) {
        return response()->json(['message' => 'No hay certificados'], 404);
    }
    
    // ✅ ADVERTENCIA si son más de 3000
    if ($total > 3000) {
        return response()->json([
            'message' => '⚠️ ADVERTENCIA: Cantidad muy grande de registros',
            'total_registros' => $total,
            'riesgos' => [
                '1. El proceso puede tardar más de 30 minutos',
                '2. El archivo ZIP puede superar 1GB',
                '3. Posible agotamiento de memoria',
                '4. El navegador puede fallar al descargar'
            ],
            'recomendaciones' => [
                '1. Usar filtros más específicos (fecha, placa, etc.)',
                '2. Exportar por lotes de 1000 registros',
                '3. Contactar soporte para procesamiento especial'
            ],
            'continuar_de_todas_formas' => url('/api/ingresos/export-certificados-zip-forzado?' . http_build_query($request->all()))
        ]);
    }
    
    // ✅ Si son entre 1000-3000, usar método optimizado
    if ($total > 1000) {
        return $this->exportMasivoConTusVistas($query, $total, $filtro);
    }
    
    // ✅ Si son menos de 1000, usar tu método actual
    return $this->exportCertificadosZip($request);
}

/**
 * Método optimizado para 1000-3000 registros usando TUS VISTAS
 */
private function exportMasivoConTusVistas($query, $total, $filtro)
{
    \Log::info("Exportando {$total} registros con método optimizado");
    
    // Crear ZIP
    $zipFileName = 'certificados-masivos-' . now()->format('Y-m-d-H-i') . '.zip';
    $zipPath = storage_path('app/temp/' . $zipFileName);
    
    if (!file_exists(dirname($zipPath))) {
        mkdir(dirname($zipPath), 0755, true);
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $archivosAgregados = 0;
        $errores = [];
        
        // ✅ OPTIMIZACIÓN 1: Procesar por LOTES de 200
        $lotes = ceil($total / 200);
        
        for ($lote = 0; $lote < $lotes; $lote++) {
            \Log::info("Procesando lote " . ($lote + 1) . "/{$lotes}");
            
            // ✅ OPTIMIZACIÓN 2: Cargar solo datos necesarios por lote
            $ingresos = $query->with([
                'avaluo' => function($q) {
                    $q->select('id', 'ingreso_id', 'tipo', 'formato', 'user_id', 
                               'valor_razonable', 'inicial', 'consecutivo');
                },
                'avaluo.clasificados' => function($q) {
                    $q->select('id', 'avaluo_id', 'modelo', 'valor')
                      ->limit(20); // ✅ Solo 20 clasificados máximo
                },
                'avaluo.corregidos' => function($q) {
                    $q->select('id', 'avaluo_id', 'modelo', 'valor')
                      ->limit(20); // ✅ Solo 20 corregidos máximo
                }
            ])
            ->skip($lote * 200)
            ->take(200)
            ->get();
            
            foreach ($ingresos as $ingreso) {
                try {
                    // ✅ OPTIMIZACIÓN 3: Decidir si generar gráfica
                    $generarGrafica = ($lote < 5); // Solo primeros 5 lotes (1000 registros)
                    
                    if ($generarGrafica && $ingreso->avaluo) {
                        // Cargar más datos si se necesita gráfica
                        if (!$ingreso->avaluo->relationLoaded('clasificados')) {
                            $ingreso->avaluo->load(['clasificados' => function($q) {
                                $q->limit(20);
                            }]);
                        }
                        if (!$ingreso->avaluo->relationLoaded('corregidos')) {
                            $ingreso->avaluo->load(['corregidos' => function($q) {
                                $q->limit(20);
                            }]);
                        }
                    }
                    
                    // ✅ Usar TU método generarPdfParaZip que ya funciona
                    $pdf = $this->generarPdfParaZip($ingreso);
                    
                    if ($pdf) {
                        $pdfContent = $pdf->output();
                        $fileNameInZip = $this->generarNombreArchivoZip($ingreso);
                        
                        if ($zip->addFromString($fileNameInZip, $pdfContent)) {
                            $archivosAgregados++;
                        }
                    }
                } catch (\Exception $e) {
                    $errores[] = $ingreso->placa . ': ' . $e->getMessage();
                    \Log::error("Error en lote {$lote}: " . $e->getMessage());
                    continue;
                }
            }
            
            // ✅ OPTIMIZACIÓN 4: Liberar memoria
            unset($ingresos);
            gc_collect_cycles();
            
            // ✅ OPTIMIZACIÓN 5: Pequeña pausa
            if ($lotes > 1) {
                usleep(100000); // 0.1 segundo
            }
        }
        
        $zip->close();
        
        \Log::info("✅ Exportación masiva completada: {$archivosAgregados}/{$total}");
        
        if ($archivosAgregados > 0) {
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }
    }
    
    return response()->json([
        'message' => 'Error en exportación masiva',
        'total' => $total,
        'generados' => $archivosAgregados ?? 0,
        'errores' => $errores ?? []
    ], 500);
}

/**
 * Método FORZADO para más de 3000 registros (solo para administradores)
 */
public function exportCertificadosZipForzado(Request $request)
{
    // ✅ Solo para administradores
    if (!auth()->user()->hasRole('admin')) {
        return response()->json([
            'message' => 'Solo administradores pueden forzar exportaciones masivas',
            'contactar' => 'Solicite al administrador esta operación'
        ], 403);
    }
    
    \Log::info('=== EXPORTACIÓN FORZADA MASIVA ===');
    
    // ✅ Límites muy altos
    ini_set('max_execution_time', 7200); // 2 horas
    ini_set('memory_limit', '4096M');    // 4GB
    
    $filtro = $request->get('filtro', '');
    $tiposervicio = 'Sec Bogota';
    $ids = $this->obtenerIdsSeleccionados($request);
    
    // Solo IDs para contar
    $query = Ingreso::query();
    $this->aplicarFiltroExportacion($query, $tiposervicio, $filtro, $ids);
    
    $total = $query->count();
    
    \Log::info("⚠️ EXPORTACIÓN FORZADA para {$total} registros");
    
    if ($total === 0) {
        return response()->json(['message' => 'No hay certificados'], 404);
    }
    
    // ✅ Crear múltiples ZIP si son más de 5000
    if ($total > 5000) {
        return $this->exportMultiplesZips($query, $total, $filtro);
    }
    
    return $this->exportMasivoConTusVistas($query, $total, $filtro);
}

/**
 * Crear múltiples ZIP para volúmenes muy grandes
 */
private function exportMultiplesZips($query, $total, $filtro)
{
    $maxPorZip = 2000; // Máximo 2000 por ZIP
    
    $numZips = ceil($total / $maxPorZip);
    
    if ($numZips > 10) {
        return response()->json([
            'message' => 'Demasiados registros para exportación simple',
            'total' => $total,
            'zips_necesarios' => $numZips,
            'recomendacion' => 'Contactar al administrador para procesamiento especial'
        ]);
    }
    
    \Log::info("Creando {$numZips} archivos ZIP para {$total} registros");
    
    $zipPrincipal = new ZipArchive();
    $zipPrincipalNombre = 'certificados-masivos-paquete-' . now()->format('Y-m-d-H-i') . '.zip';
    $zipPrincipalPath = storage_path('app/temp/' . $zipPrincipalNombre);
    
    if (!file_exists(dirname($zipPrincipalPath))) {
        mkdir(dirname($zipPrincipalPath), 0755, true);
    }
    
    if ($zipPrincipal->open($zipPrincipalPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        
        for ($zipNum = 0; $zipNum < $numZips; $zipNum++) {
            \Log::info("Generando sub-ZIP " . ($zipNum + 1) . "/{$numZips}");
            
            $offset = $zipNum * $maxPorZip;
            
            // Crear sub-ZIP
            $subZip = new ZipArchive();
            $subZipNombre = "certificados-parte-" . ($zipNum + 1) . "-de-{$numZips}.zip";
            $subZipPath = storage_path('app/temp/' . $subZipNombre);
            
            if ($subZip->open($subZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $subQuery = clone $query;
                
                $ingresos = $subQuery->skip($offset)
                                    ->take($maxPorZip)
                                    ->get();
                
                $archivosEnSubZip = 0;
                
                foreach ($ingresos as $ingreso) {
                    try {
                        // ✅ VERSIÓN MUY SIMPLE para velocidad
                        $pdf = $this->generarPdfParaZip($ingreso);
                        
                        if ($pdf) {
                            $pdfContent = $pdf->output();
                            $fileNameInZip = $this->generarNombreArchivoZip($ingreso);
                            
                            if ($subZip->addFromString($fileNameInZip, $pdfContent)) {
                                $archivosEnSubZip++;
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error en sub-ZIP {$zipNum}: " . $e->getMessage());
                        continue;
                    }
                }
                
                $subZip->close();
                
                // Agregar sub-ZIP al ZIP principal
                if (file_exists($subZipPath)) {
                    $zipPrincipal->addFile($subZipPath, $subZipNombre);
                    \Log::info("Sub-ZIP {$subZipNombre} creado con {$archivosEnSubZip} archivos");
                }
                
                // Liberar memoria
                unset($ingresos);
                gc_collect_cycles();
            }
        }
        
        $zipPrincipal->close();
        
        // Eliminar sub-ZIPs temporales
        for ($zipNum = 0; $zipNum < $numZips; $zipNum++) {
            $subZipPath = storage_path('app/temp/certificados-parte-' . ($zipNum + 1) . "-de-{$numZips}.zip");
            if (file_exists($subZipPath)) {
                unlink($subZipPath);
            }
        }
        
        return response()->download($zipPrincipalPath, $zipPrincipalNombre)->deleteFileAfterSend(true);
    }
    
    return response()->json(['message' => 'Error creando ZIP principal'], 500);
}

/**
 * Optimizar el método generarPdfParaZip para masivo
 */
private function generarPdfParaZipOptimizado(Ingreso $ingreso, $modoMasivo = false)
{
    try {
        \Log::info('Generando PDF optimizado para: ' . $ingreso->placa);
        
        $avaluo = $ingreso->avaluo;
        
        if (!$avaluo) {
            \Log::error('No hay objeto avaluo', ['ingreso_id' => $ingreso->id]);
            return null;
        }
        
        $user = \App\Models\User::find($avaluo->user_id);
        
        // ✅ DECISIÓN: Generar gráfica solo si NO es modo masivo
        $graficaPath = null;
        $resultado = null;
        
        if (!$modoMasivo && in_array($avaluo->tipo, ['comercial', 'jans'])) {
            // Solo generar gráfica si hay corregidos
            if ($avaluo->corregidos && $avaluo->corregidos->isNotEmpty()) {
                $graficaPath = $this->generarGraficaDispercionOptimizada($avaluo);
                
                // Calcular resultado
                $corregidos = collect($avaluo->corregidos)->map(fn($c) => [
                    'x' => (int) $c->modelo,
                    'y' => (float) $c->valor
                ])->toArray();
                
                $modeloConsultar = (int) $ingreso->modelo;
                $resultado = $this->calcularExponencial($corregidos, $modeloConsultar);
            }
        }
        
        // Usar tus vistas existentes
        if ($avaluo->formato == 'Sec. Movilidad Bogotá' || $ingreso->tiposervicio === "Sec Bogota") {
            return Pdf::loadView('pdf.avaluosecbogota', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        } else {
            return Pdf::loadView('pdf.avaluojans', compact('ingreso', 'avaluo', 'graficaPath', 'resultado', 'user'));
        }
        
    } catch (\Exception $e) {
        \Log::error('Error en generarPdfParaZipOptimizado: ' . $e->getMessage());
        return null;
    }
}

/**
 * Método principal mejorado con detección automática
 */
public function exportCertificadosZipBackground(Request $request)
{
    $user = auth()->user();

    if (! $user || empty($user->email)) {
        return response()->json([
            'message' => 'El usuario autenticado no tiene un correo configurado para enviar la ruta de descarga.'
        ], 422);
    }

    $filtro = trim((string) ($request->get('filtro', '') ?? ''));
    $ids = $this->obtenerIdsSeleccionados($request);
    $exportaTodosFiltrados = empty($ids);

    $query = Ingreso::query();
    $this->aplicarFiltroExportacion($query, 'Sec Bogota', $filtro, $ids);
    $total = $query->count();

    if ($total === 0) {
        return response()->json([
            'message' => 'No hay certificados con PDF disponible para exportar con el filtro actual.'
        ], 404);
    }

    GenerateCertificadosZipJob::dispatch($user->id, $filtro, $ids, $exportaTodosFiltrados)
        ->onQueue(config('queue.connections.database.queue', 'default'));

    return response()->json([
        'message' => 'La generación del ZIP quedó en segundo plano. Recibirás un correo con la ruta de descarga cuando esté listo.',
        'email' => $user->email,
        'total_estimado' => $total,
        'modo' => $exportaTodosFiltrados ? 'todos_filtrados' : 'seleccion_manual',
    ], 202);
}

public function exportCertificadosZipMejorado(Request $request)
{
    $filtro = $request->get('filtro', '');
    $tiposervicio = 'Sec Bogota';
    $ids = $this->obtenerIdsSeleccionados($request);
    
    // Contar registros
    $query = Ingreso::query();
    $this->aplicarFiltroExportacion($query, $tiposervicio, $filtro, $ids);
    
    $total = $query->count();
    
    \Log::info("📊 Total registros detectados: {$total}");
    
    // ✅ DETECCIÓN AUTOMÁTICA del mejor método
    if ($total <= 100) {
        \Log::info("Usando método NORMAL (<= 100 registros)");
        return $this->exportCertificadosZip($request); // Tu método original
    } 
    elseif ($total <= 1000) {
        \Log::info("Usando método OPTIMIZADO (100-1000 registros)");
        return $this->exportMasivoConTusVistas($query, $total, $filtro);
    } 
    elseif ($total <= 3000) {
        \Log::info("Usando método MASIVO (1000-3000 registros)");
        return $this->exportCertificadosZipOptimizado($request);
    } 
    else {
        \Log::info("Usando método ESPECIAL (> 3000 registros)");
        return response()->json([
            'message' => 'Exportación especial requerida',
            'total_registros' => $total,
            'recomendacion' => 'Contactar al administrador',
            'metodos_disponibles' => [
                'exportar_por_fechas' => url('/api/ingresos/export-por-fecha'),
                'exportar_por_lotes' => url('/api/ingresos/export-por-lotes'),
                'solicitar_procesamiento_nocturno' => 'Contactar soporte'
            ]
        ]);
    }
}

private function generarGraficaDispercionOptimizada(Avaluo $avaluo)
{
    // ✅ CACHÉ INTELIGENTE: Guardar gráfica por 24 horas
    $cacheKey = "grafica_avaluo_{$avaluo->id}_" . md5(json_encode([
        'clasificados_count' => $avaluo->clasificados ? $avaluo->clasificados->count() : 0,
        'corregidos_count' => $avaluo->corregidos ? $avaluo->corregidos->count() : 0,
        'ultima_actualizacion' => $avaluo->updated_at
    ]));
    
    // ✅ VERIFICAR CACHÉ PRIMERO
    if (Cache::has($cacheKey)) {
        $filename = Cache::get($cacheKey);
        $filepath = public_path("graficas/{$filename}");
        
        if (file_exists($filepath)) {
            \Log::info("✅ Gráfica desde CACHÉ para avaluo {$avaluo->id}");
            return $filename;
        }
    }
    
    // ✅ Si no hay datos para gráfica, retornar null rápido
    $tieneClasificados = $avaluo->clasificados && $avaluo->clasificados->isNotEmpty();
    $tieneCorregidos = $avaluo->corregidos && $avaluo->corregidos->isNotEmpty();
    
    if (!$tieneClasificados && !$tieneCorregidos) {
        \Log::info("⏭️ Sin datos para gráfica, avaluo {$avaluo->id}");
        return null;
    }
    
    // ✅ LIMITAR DATOS para gráfica (máximo 30 puntos por dataset)
    $clasificados = [];
    $corregidos = [];
    
    if ($tieneClasificados) {
        $clasificados = $avaluo->clasificados->take(30)->map(function($c) {
            return [
                'x' => is_numeric($c->modelo) ? (float)$c->modelo : 0,
                'y' => is_numeric($c->valor) ? (float)$c->valor : 0
            ];
        })->filter(fn($p) => $p['y'] > 0)->toArray();
    }
    
    if ($tieneCorregidos) {
        $corregidos = $avaluo->corregidos->take(30)->map(function($c) {
            return [
                'x' => is_numeric($c->modelo) ? (float)$c->modelo : 0,
                'y' => is_numeric($c->valor) ? (float)$c->valor : 0
            ];
        })->filter(fn($p) => $p['y'] > 0)->toArray();
    }
    
    // ✅ Si después de filtrar no hay datos, retornar null
    if (empty($clasificados) && empty($corregidos)) {
        return null;
    }
    
    // ✅ Preparar datasets optimizados
    $datasets = [];
    
    // Clasificados
    if (!empty($clasificados)) {
        $datasets[] = [
            'label' => 'Clasificados',
            'data' => $clasificados,
            'backgroundColor' => 'rgba(54,162,235,0.8)',
            'borderColor' => 'rgba(54,162,235,1)',
            'showLine' => false,
            'pointRadius' => 3,
        ];
    }
    
    // Corregidos
    if (!empty($corregidos)) {
        $datasets[] = [
            'label' => 'Corregidos',
            'data' => $corregidos,
            'backgroundColor' => 'rgba(255,99,132,0.8)',
            'borderColor' => 'rgba(255,99,132,1)',
            'showLine' => false,
            'pointRadius' => 3,
        ];
    }
    
    // ✅ Solo calcular regresiones si hay suficientes puntos
    $regClas = null;
    $regCorr = null;
    
    if (count($clasificados) >= 2) {
        $regClas = $this->calcularRegresionRapida($clasificados);
    }
    
    if (count($corregidos) >= 2) {
        $regCorr = $this->calcularRegresionRapida($corregidos);
    }
    
    // Agregar líneas de regresión
    if ($regClas) {
        $datasets[] = [
            'label' => 'f(x) Clasificados',
            'data' => $regClas['curve'],
            'type' => 'line',
            'borderColor' => 'rgba(54,162,235,1)',
            'borderDash' => [5, 5],
            'fill' => false,
            'pointRadius' => 0,
            'borderWidth' => 1,
        ];
    }
    
    if ($regCorr) {
        $datasets[] = [
            'label' => 'f(x) Corregidos',
            'data' => $regCorr['curve'],
            'type' => 'line',
            'borderColor' => 'rgba(255,99,132,1)',
            'borderDash' => [5, 5],
            'fill' => false,
            'pointRadius' => 0,
            'borderWidth' => 1,
        ];
    }
    
    // ✅ Configuración MINIMALISTA para QuickChart
    $chartConfig = [
        'type' => 'scatter',
        'data' => ['datasets' => $datasets],
        'options' => [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'top'],
                'title' => ['display' => false], // Sin título para ahorrar espacio
            ],
            'scales' => [
                'x' => ['title' => ['display' => true, 'text' => 'Modelo']],
                'y' => ['title' => ['display' => true, 'text' => 'Valor']],
            ],
            'responsive' => false, // Desactivar responsive para mejor performance
        ]
    ];
    
    try {
        // ✅ QuickChart con timeout corto
        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 8, // 8 segundos máximo
            'connect_timeout' => 5,
        ]);
        
        $response = $client->post('https://quickchart.io/chart', [
            'json' => [
                'chart' => $chartConfig,
                'width' => 600,  // Reducido de 800
                'height' => 400, // Reducido de 500
                'format' => 'png',
                'version' => '4',
            ]
        ]);
        
        if ($response->getStatusCode() === 200) {
            $filename = "avaluo_{$avaluo->id}.png";
            $filepath = public_path("graficas/{$filename}");
            
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0777, true);
            }
            
            file_put_contents($filepath, $response->getBody());
            
            // ✅ Guardar en caché por 24 horas
            Cache::put($cacheKey, $filename, now()->addHours(24));
            
            \Log::info("✅ Gráfica generada y caché para avaluo {$avaluo->id}");
            return $filename;
        }
    } catch (\Exception $e) {
        \Log::warning("⚠️ QuickChart falló para avaluo {$avaluo->id}: " . $e->getMessage());
        // Si falla, no generar gráfica local para no consumir tiempo
        return null;
    }
    
    return null;
}

/**
 * Calcular regresión RÁPIDA (sin R² para ahorrar tiempo)
 */
private function calcularRegresionRapida(array $puntos)
{
    if (count($puntos) < 2) {
        return null;
    }
    
    $xs = array_column($puntos, 'x');
    $ys = array_column($puntos, 'y');
    
    // Solo puntos con y > 0
    $pts = [];
    for ($i = 0; $i < count($xs); $i++) {
        if ($ys[$i] > 0) {
            $pts[] = ['x' => $xs[$i], 'y' => $ys[$i]];
        }
    }
    
    if (count($pts) < 2) {
        return null;
    }
    
    $xs = array_column($pts, 'x');
    $ys = array_column($pts, 'y');
    
    // Transformación logarítmica
    $logYs = array_map('log', $ys);
    
    $n = count($xs);
    $sumX = array_sum($xs);
    $sumLogY = array_sum($logYs);
    $sumXLogY = 0;
    $sumX2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumXLogY += $xs[$i] * $logYs[$i];
        $sumX2 += $xs[$i] * $xs[$i];
    }
    
    $denominador = $n * $sumX2 - $sumX * $sumX;
    if (abs($denominador) < 1e-12) {
        return null;
    }
    
    $b = ($n * $sumXLogY - $sumX * $sumLogY) / $denominador;
    $lnA = ($sumLogY - $b * $sumX) / $n;
    $a = exp($lnA);
    
    // Solo 20 puntos para la curva (en lugar de 100)
    $minX = min($xs);
    $maxX = max($xs);
    $curve = [];
    
    for ($i = 0; $i <= 20; $i++) {
        $x = $minX + ($maxX - $minX) * ($i / 20);
        $y = $a * exp($b * $x);
        $curve[] = ['x' => $x, 'y' => $y];
    }
    
    return ['curve' => $curve, 'a' => $a, 'b' => $b];
}


}
