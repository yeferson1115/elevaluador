<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;  // <- importa esta interfaz
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject  // <- implementa la interfaz
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'document',
        'phone',
        'password',
        'tarjeta_profecional',
        'r_aa',
        'profesion'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Métodos requeridos por JWTSubject:

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
