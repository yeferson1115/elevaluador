<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvaluoClasificado extends Model
{
    protected $table = 'avaluo_clasificados';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = ['avaluo_id', 'modelo', 'valor'];

   
}

