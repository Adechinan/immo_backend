<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'departement_id'];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function arrondissements()
    {
        return $this->hasMany(Arrondissement::class);
    }

    public function quartiers()
    {
        return $this->hasMany(Quartier::class);
    }
}
