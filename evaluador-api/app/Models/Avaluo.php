<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avaluo extends Model
{
    protected $table = 'avaluos';
    protected $fillable = [
        'ingreso_id',
        'tipo',
        'formato',
        'observaciones',
        'vida_util_probable',
        'vida_usada_dias',
        'vida_usada_meses',
        'vida_usada_anos',
        'vida_util_remate',
        'vida_util_anos',
        'antiguedad',
        'vida_util',
        'valor_reposicion',
        'valor_residual',
        'estado_conservacion',
        'x',
        'k',
        'valor_resonable',
        'capacidad_transportadora',
        'valor_razonable',
        'valor_carroceria',
        'valor_reparaciones',
        'valor_llantas',
        'valor_pintura',
        'valor_overhaul_motor',
        'factor_demerito',
        'valor_accesorios',
        'indice_responsabilidad_minimo',
        'avaluo_total',
        'no_factura',
        'declaracion_importacion',
        'fecha_importacion',
        'registro_maquinaria',
        'gps',
        'fecha_inspeccion',
        'file',
        'avaluador',
        'user_id',
        'llanta_delantera_izquierda',
        'llanta_delantera_derecha',
        'llanta_trasera_izquierda',
        'llanta_trasera_derecha',
        'llanta_repuesto',
        'latoneria_estado', 
        'latoneria_valor',
        'pintura_estado',
        'tapiceria_estado', 
        'tapiceria_valor',
        'motor_estado', 
        'motor_valor',
        'chasis_estado', 
        'chasis_valor',
        'transmision_estado', 
        'transmision_valor',
        'frenos_estado', 
        'frenos_valor',
        'refrigeracion_estado', 
        'refrigeracion_valor',
        'electrico_estado', 
        'electrico_valor',
        'tanque_estado', 
        'tanque_valor',
        'bateria_estado', 
        'bateria_valor',
        'llantas_estado', 
        'llantas_valor',
        'llave_estado',
        'llave_valor',
        'vidrios_estado', 
        'vidrios_valor',
        'chatarra',
        'valor_chatarra_kg',
        'peso_chatarra_kg',
        'valor_RTM',
        'valor_SOAT',
        'valor_faltantes',
        'codigo_fasecolda',
        'porc_reposicion',
        'ubicacion',
        'code_movilidad',
        'evaluador',
        'consecutivo',
        'inicial'
    ];


    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }

    public function clasificados()
    {
        return $this->hasMany(AvaluoClasificado::class);
    }

    public function corregidos()
    {
        return $this->hasMany(AvaluoCorregido::class);
    }

     public function limitaciones()
    {
        return $this->hasMany(AvaluoLimitaciones::class);
    }
    

	
	

}
