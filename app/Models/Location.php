<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bien_en_location_id', 'dateLocation'];

    protected function casts(): array
    {
        return [
            'dateLocation' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bienEnLocation()
    {
        return $this->belongsTo(BiensEnLocation::class, 'bien_en_location_id');
    }

    public function mensualites()
    {
        return $this->hasMany(Mensualite::class);
    }
}
