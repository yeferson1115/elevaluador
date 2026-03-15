<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionExterior extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_exterior';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'vidrios',
        'tapiceria_accesorios',
        'fugas_fluidos',
        'ajuste_cierre_capo',
        'ajuste_cierre_puestas_delantera_izq',
        'ajuste_cierre_puestas_delantera_der',
        'ajuste_cierre_puertas_trasera_izq',
        'ajuste_cierre_puertas_trasera_der',
        'ajuste_cierre_tapa_baul_compuerta',
    ];

    // Relación inversa: pertenece a una inspección
    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
