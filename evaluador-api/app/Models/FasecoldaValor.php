<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasecoldaValor extends Model
{
    protected $table = 'fasecolda_valores';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'codigo_fasecolda',
        'tipo',
        'modelo',
        'valor',
        'peso_vacio'
    ];

   
}

