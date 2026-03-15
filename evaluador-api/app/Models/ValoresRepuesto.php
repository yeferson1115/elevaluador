<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValoresRepuesto extends Model
{
    use HasFactory;

    protected $table = 'valores_repuestos';
    
    protected $fillable = [
        'cilindraje_to',
        'cilindraje_from',
        'tipo',
        'especial',
        'llantas',
        'tapiceria',
        'soat',
        'rtm',
        'kit_arrastre',
        'motor_mantenimiento',
        'pintura',
        'latoneria',
        'chasis',
        'frenos',
        'bateria',
        'tanque_combustible',
        'llave',
        'sis_electrico'
    ];

    protected $casts = [
        'especial' => 'boolean',
    ];
}
