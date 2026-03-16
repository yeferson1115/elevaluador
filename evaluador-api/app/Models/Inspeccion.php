<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspeccion extends Model
{
    use HasFactory;

    protected $table = 'inspeccion'; // nombre de la tabla (opcional si sigue la convención)

    protected $primaryKey = 'id'; // clave primaria

    public $timestamps = true; // usa created_at y updated_at

    // Si los timestamps pueden ser null, puedes usar:
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ingreso_id',
        'aseguradora',
        'intermediaria',
        'combustible',
        'tipo_pintura',
        'servicio',
        'kilometraje',
        'color',
        'centro_inspeccion',
        'valor_mercado',
        'valor_evaluador',
        'valor_accesorios',
        'resultado',
        'intermediario',
        'turno',
        'cod_fasecolda',
        'valor_fasecolda',
        'novedades_inspeccion',
        'ciudad',
        'observaciones',
        'expide_para',
        'file',
        'inspector',
        'user_id',
        'tipo_vehiculo'
    ];

    public function inspeccionExterior()
    {
        return $this->hasOne(InspeccionExterior::class);
    }
    public function inspeccionFuncionamiento()
    {
        return $this->hasOne(InspeccionFuncionamiento::class);
    }
    public function inspeccionIndicadores()
    {
        return $this->hasOne(InspeccionIndicadores::class);
    }
    public function inspeccionLuces()
    {
        return $this->hasOne(InspeccionLuces::class);
    }
    public function inspeccionMecanica()
    {
        return $this->hasOne(InspeccionMecanica::class);
    }
    public function inspeccionTapiceria()
    {
        return $this->hasOne(InspeccionTapiceria::class);
    }
    public function inspeccionAccesorios()
    {
        return $this->hasMany(InspeccionAccesorios::class);
    }
    public function inspeccionParteBaja()
    {
        return $this->hasOne(InspeccionParteBaja::class);
    }

    public function inspeccionVisual()
    {
        return $this->hasMany(InspeccionVisual::class);
    }

    public function inspeccionRevisionVisual()
    {
        return $this->hasOne(InspeccionRevisionVisual::class);
    }

    public function inspeccionRevisionVisualPuntoLiviano()
    {
        return $this->hasOne(InspeccionRevisionVisualPuntoLiviano::class);
    }

    







    public function inspeccionRevisionVisualPuntoMoto()
    {
        return $this->hasOne(InspeccionRevisionVisualPuntoMoto::class);
    }
}
