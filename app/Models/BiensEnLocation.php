<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiensEnLocation extends Model
{
    use HasFactory;

    protected $table = 'biens_en_location';

    protected $fillable = ['bien_id'];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'bien_en_location_id');
    }
}
