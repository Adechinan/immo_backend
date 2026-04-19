<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function biens()
    {
        return $this->belongsToMany(Bien::class, 'bien_option');
    }
}
