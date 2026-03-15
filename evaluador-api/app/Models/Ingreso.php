<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
{
     protected $table = 'ingresos';
    protected $fillable = [
    'tiposervicio',
	'solicitante',
	'documento_solicitante',
	'direccion_solicitante',
	'telefono_solicitante',
	'placa',
	'ubicacion_activo',
	'fecha_solicitud',
	'fecha_inspeccion',
	'fecha_informe',
	'objeto_avaluo',
	'codigo_interno_movil',
	'tipo_propiedad',
	'fecha_matricula',
	'movil',
	'marca',
	'linea',
	'clase',
	'tipo_carroceria',
	'categoria',
	'color',
	'cilindraje',
	'modelo',
	'kilometraje',
	'caja_cambios',
	'tipo_traccion',
	'numero_pasajeros',
	'capacidad_carga',
	'llanta_delantera_izquierda',
	'llanta_delantera_derecha',
	'llanta_trasera_izquierda',
	'llanta_trasera_derecha',
	'llanta_repuesto',
	'numero_chasis',
	'numero_serie',
	'numero_motor',
	'nacionalidad',
	'propietario',
	'empresa_afiliacion',
	'soat',
	'fecha_expedicion_soat',
	'fecha_inicio_vigencia_soat',
	'fecha_vencimiento_soat',
	'entidad_expide_soat',
	'estado_soat',
	'rtm',
	'fecha_vencimiento_rtm',
	'centro_revision_rtm',
	'estado_rtm',
	'ciudad_registro',
	'no_licencia',
	'fecha_expedicion_licencia',
	'organismo_transito',
	'estado',
	'fecha_inicial_matricula',
	'estado_matricula',
	'traslados_matricula',
	'tipo_servicio_vehiculo',
	'cambios_tipo_servicio',
	'fecha_ult_cambio_servicio',
	'cambio_color_historica',
	'fecha_ult_cambio_color',
	'color_cambiado',
	'cambios_blindaje',
	'fecha_cambio_blindaje',
	'repotenciado',
	'tiene_gravamedes',
	'tiene_prenda',
	'regrabado_no_motor',
	'regrabado_no_chasis',
	'regrabado_no_serie',
	'regrabado_no_vin',
	'limitacion_propiedad',
	'numero_doc_proceso',
	'entidad_juridica',
	'tipo_doc_demandante',
	'no_identificacion_demandante',
	'fecha_expedicion_novedad',
	'fecha_radicacion',
	'cantidad_ejes',
	'peso_bruto',
	'numeroVin',
	'documento_propietario',
	'estado_registro_runt',
	'fecha_ingreso',
	'peso_mermado',
	'capacidad_ton'
    ];


   
	
	

    public function avaluo()
    {
        return $this->hasOne(Avaluo::class, 'ingreso_id');
    }

    public function inspeccion()
    {
        return $this->hasOne(Inspeccion::class, 'ingreso_id');
    }

    
    public function images()
    {
        return $this->hasMany(IngresoImage::class,'avaluo_id');
    }

	public function historicoPropietarios()
	{
		return $this->hasMany(HistoricoPropietario::class, 'ingreso_id');
	}

}
