<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionIndicadores extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_indicadores';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'testigo_abs',
        'testigo_aceite',
        'testigo_airbag',
        'testigo_check_engine',
        'testigo_frenos',
        'testigo_combustible',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
