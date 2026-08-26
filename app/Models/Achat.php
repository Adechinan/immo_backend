<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bien_id', 'dateAchat', 'nom_manuel', 'email_manuel', 'tel_manuel'];

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

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }
}
