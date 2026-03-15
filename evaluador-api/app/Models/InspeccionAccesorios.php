<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionAccesorios extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_accesorios'; // Nombre exacto de la tabla

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'decripcion',
        'marca_ref',
        'cantidad',
        'valor',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
