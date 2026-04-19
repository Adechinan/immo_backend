<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiensEnVente extends Model
{
    use HasFactory;

    protected $table = 'biens_en_vente';

    protected $fillable = ['bien_id'];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function achats()
    {
        return $this->hasMany(Achat::class, 'bien_en_vente_id');
    }
}
