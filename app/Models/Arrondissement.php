<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arrondissement extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville_id'];

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }

    public function quartiers()
    {
        return $this->hasMany(Quartier::class);
    }
}
