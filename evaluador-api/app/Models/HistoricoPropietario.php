<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoPropietario extends Model
{
    use HasFactory;

    protected $table = 'historico_propietarios';

    protected $fillable = [
        'nombre_empresa',
        'tipo_propietario',
        'tipo_identificacion',
        'numero_identificacion',
        'fecha_inicio',
        'estado',
        'ingreso_id', // relación con Ingreso
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
    ];

    /**
     * Relación con Ingreso
     */
    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }
}
