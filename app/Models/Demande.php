<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'bien_id',
        'user_id',
        'message_demandeur',
        'email_demandeur',
        'contact_demandeur',
        'reponse',
        'repondu_at',
    ];

    protected function casts(): array
    {
        return [
            'repondu_at' => 'datetime',
        ];
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
