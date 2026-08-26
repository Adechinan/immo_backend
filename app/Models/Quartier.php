<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quartier extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville_id', 'arrondissement_id'];

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }

    public function arrondissement()
    {
        return $this->belongsTo(Arrondissement::class);
    }
}
