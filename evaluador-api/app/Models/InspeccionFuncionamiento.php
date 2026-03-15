<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionFuncionamiento extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_funcionamiento';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'asientos_delantero',
        'bocina',
        'calefaccion',
        'desempanador',
        'ecendedor',
        'espejos_electricos',
        'limpiabrisas_del',
        'limpiabrisas_tra',
        'luz_interior',
        'radio',
        'encendido_arranque',
        'tacometro',
        'techo_corredizo',
        'velocimetro',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
