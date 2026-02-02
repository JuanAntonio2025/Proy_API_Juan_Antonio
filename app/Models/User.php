<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; // <--- 1. Importar la interfaz

class User extends Authenticatable implements JWTSubject // <--- 2. Implementar la interfaz
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // --- 3. Añadir estos dos métodos obligatorios ---

    /**
     * Obtiene el identificador que se guardará en el "subject" del JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Permite añadir campos personalizados al payload del token.
     * Por ejemplo, si quieres que el 'role' vaya dentro del token.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }

    // --- Fin de métodos JWT ---

    public function petitions() {
        return $this->hasMany(Petition::class, 'user_id');
    }

    public function signedPetitions() {
        return $this->belongsToMany(Petition::class, 'petition_user', 'user_id', 'petition_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
