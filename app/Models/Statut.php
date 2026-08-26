<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statut extends Model
{
    protected $fillable = ['code', 'libelle', 'couleur'];

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }
}
