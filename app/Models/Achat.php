<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bien_en_vente_id', 'dateAchat'];

    protected function casts(): array
    {
        return [
            'dateAchat' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bienEnVente()
    {
        return $this->belongsTo(BiensEnVente::class, 'bien_en_vente_id');
    }
}
