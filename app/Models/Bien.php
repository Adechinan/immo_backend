<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'categorie',
        'surface',
        'description',
        'pieces',
        'chambres',
        'salons',
        'etage',
        'adresse',
        'ville',
        'codePostal',
        'latitude',
        'longitude',
        'statut_id',
        'prix',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'surface'   => 'decimal:2',
            'prix'      => 'decimal:2',
            'latitude'  => 'decimal:7',
            'longitude' => 'decimal:7',
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

    public function statut()
    {
        return $this->belongsTo(Statut::class);
    }

    public function achats()
    {
        return $this->hasMany(Achat::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }

    // Scopes
    public function scopeStatutCode($query, string|array $code)
    {
        return $query->whereHas('statut', fn ($q) => $q->whereIn('code', (array) $code));
    }

    public function scopeDisponible($query)
    {
        return $query->statutCode(['a_vendre', 'a_louer']);
    }

    public function scopeEnVente($query)
    {
        return $query->statutCode(['a_vendre', 'vendu']);
    }

    public function scopeEnLocation($query)
    {
        return $query->statutCode(['a_louer', 'loue']);
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
