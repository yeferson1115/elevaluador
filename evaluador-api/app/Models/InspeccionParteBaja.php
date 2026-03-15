<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionParteBaja extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_parte_baja';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'carter',
        'cauchos_suspension',
        'guardapolvos_caja_direccion',
        'guardapolvos_eje',
        'protectores_inferiores',
        'estado_catalizador',
        'estado_silenciador_escape',
        'estado_tijeras',
        'estado_tuberias_frenos',
        'estado_tubo_exhosto',
        'fuga_aceite_caja_velocidades',
        'fuga_direccion_hidraulica',
        'fuga_aceite_motor',
        'fuga_amortiguadores',
        'fuga_liquido_embrague',
        'fuga_liquido_frenos',
        'fuga_combustible_tanque',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
