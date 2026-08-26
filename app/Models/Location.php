<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bien_id', 'dateLocation', 'jour_encaissement', 'date_fin', 'nom_manuel', 'email_manuel', 'tel_manuel'];

    protected function casts(): array
    {
        return [
            'dateLocation' => 'date',
            'date_fin'     => 'date',
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

    public function mensualites()
    {
        return $this->hasMany(Mensualite::class);
    }
}
