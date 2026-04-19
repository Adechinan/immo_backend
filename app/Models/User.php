<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'prenom',
        'nom',
        'tel',
        'email',
        'password',
        'type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relations
    public function achats()
    {
        return $this->hasMany(Achat::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function biensAchetes()
    {
        return $this->hasManyThrough(BiensEnVente::class, Achat::class, 'user_id', 'id', 'id', 'bien_en_vente_id');
    }
}
