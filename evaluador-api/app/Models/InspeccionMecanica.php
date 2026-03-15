<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionMecanica extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_mecanica';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'kilometraje',
        'funcionamiento_a_a',
        'nivel_aceite_direccion_hidraulica',
        'nivel_aceite_motor',
        'nivel_agua_limpiavidrios',
        'nivel_liquido_frenos',
        'nivel_liquido_embrague',
        'nivel_refrigerante_motor',
        'soportes_caja_velocidades',
        'viscosidad_aceite_motor',
        'estado_cables_instalacion_alta',
        'estado_carcasa_caja_velocidades',
        'estado_correas',
        'estado_externo_bateria',
        'estado_filtro_aire',
        'estado_manqgueras_radiador',
        'estado_radiador',
        'estado_radiador_a_a',
        'estado_soporte_motor',
        'tension_correas',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
