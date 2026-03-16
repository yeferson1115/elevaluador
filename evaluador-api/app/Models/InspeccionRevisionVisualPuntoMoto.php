<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspeccionRevisionVisualPuntoMoto extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_rev_visual_punto_moto';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'unidad_farola_moto',
        'visera',
        'direccionales_moto',
        'manillar',
        'espejo_izq_moto',
        'espejo_der_moto',
        'carenaje_delantero',
        'horquilla',
        'guardafango_frontal',
        'tanque_combustible',
        'sillon',
        'chasis',
        'estribo_moto',
        'tapa_lateral_izq',
        'tapa_lateral_der',
        'tapa_trasera_izq',
        'tapa_trasera_der',
        'guardafango_trasero',
        'stop_moto',
        'pata',
        'caballete',
        'mango_calapie',
        'maleta',
        'cofre_trasero',
        'barra_telescopica_izq',
        'barra_telescopica_der',
        'amortiguador_trasero_moto',
        'motor_moto',
        'kit_arrastre',
        'sistema_escape',
        'bateria_moto',
        'mango_acelerador',
        'manigueta_freno',
        'manigueta_embrague',
        'deposito_liquido_hidraulico',
        'tablero_instrumentos',
        'pedal_freno',
        'pedal_cambios',
        'disco_campana_delantera',
        'disco_campana_trasera',
        'aceite_motor_fugas',
        'combustible_fugas',
        'llanta_delantera_moto',
        'llanta_trasera_moto',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
