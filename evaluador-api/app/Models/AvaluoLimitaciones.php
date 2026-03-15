<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvaluoLimitaciones extends Model
{
    protected $table = 'avaluo_limitaciones';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = ['avaluo_id', 'texto'];

   
}

