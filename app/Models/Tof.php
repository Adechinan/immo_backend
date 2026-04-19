<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tof extends Model
{
    use HasFactory;

    protected $fillable = ['bien_id', 'src'];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }
}
