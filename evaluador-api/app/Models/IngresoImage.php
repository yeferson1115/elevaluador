<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoImage extends Model
{
    use HasFactory;
    protected $table = 'ingreso_images';

    // En esta tabla, avaluo_id almacena el ID del ingreso relacionado.
    protected $fillable = [
        'avaluo_id',
        'categoria',
        'path',
        'orden',
        'rotacion',
    ];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

   
}
