<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'surface',
        'description',
        'pieces',
        'chambres',
        'etage',
        'adresse',
        'ville',
        'codePostal',
        'statut',
        'prix',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'surface' => 'decimal:2',
            'prix'    => 'decimal:2',
        ];
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tofs()
    {
        return $this->hasMany(Tof::class);
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'bien_option');
    }

    public function bienEnVente()
    {
        return $this->hasOne(BiensEnVente::class);
    }

    public function bienEnLocation()
    {
        return $this->hasOne(BiensEnLocation::class);
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }

    // Scopes
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeEnVente($query)
    {
        return $query->whereHas('bienEnVente');
    }

    public function scopeEnLocation($query)
    {
        return $query->whereHas('bienEnLocation');
    }

    public function scopeVille($query, string $ville)
    {
        return $query->where('ville', $ville);
    }

    public function scopePrixMax($query, float $prix)
    {
        return $query->where('prix', '<=', $prix);
    }

    public function scopePrixMin($query, float $prix)
    {
        return $query->where('prix', '>=', $prix);
    }
}
