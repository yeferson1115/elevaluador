<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionRevisionVisual extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_rev_visual';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'pintura',
        'desviacion_km',
        'ruedas_traseras',
        'ruedas_delanteras',
        'llanta_del_izq',
        'llanta_del_der',
        'llanta_tras_izq',
        'llanta_tras_der',
        'freno_mano',
        'suspension_tras',
        'suspension_delantera',
    ];

   
	
	

    // Relación inversa: pertenece a una inspección
    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
