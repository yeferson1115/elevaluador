<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionVisual extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_visual'; // Nombre exacto de la tabla

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'zona',
        'estado'
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
