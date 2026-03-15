<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvaluoCorregido extends Model
{
    protected $table = 'avaluo_corregidos';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = ['avaluo_id', 'modelo', 'valor'];

   
}

