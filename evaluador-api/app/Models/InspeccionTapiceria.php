<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionTapiceria extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_tapiceria';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'inspeccion_id',
        'estado_timon',
        'estados_tapizados_puerta',
        'estados_tapizado_asientos',
        'estado_tapiceria_techo',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
