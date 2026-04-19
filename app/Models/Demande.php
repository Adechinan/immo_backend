<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'bien_id',
        'message_demandeur',
        'email_demandeur',
        'contact_demandeur',
    ];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }
}
