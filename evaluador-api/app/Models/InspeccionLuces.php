<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspeccionLuces extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_luces';

    protected $primaryKey = 'id';

    public $timestamps = true; // created_at y updated_at están presentes

    protected $fillable = [
        'inspeccion_id',
        'direccionales',
        'luces_altas',
        'luces_bajas',
        'luces_exploradoras',
        'luces_frenos',
        'lueces_medias',
        'luces_parqueo',
        'luces_placa',
        'luces_reversa',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class);
    }
}
