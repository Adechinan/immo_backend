<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'piece_identite_path',
        'registre_commerce_path',
        'statut',
        'commentaire_admin',
        'traite_par',
        'traite_le',
    ];

    protected function casts(): array
    {
        return [
            'traite_le' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function traitant()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }
}
