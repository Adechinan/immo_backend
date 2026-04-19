<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensualite extends Model
{
    use HasFactory;

    protected $fillable = ['location_id', 'datePaiement', 'dateLoyer'];

    protected function casts(): array
    {
        return [
            'datePaiement' => 'date',
            'dateLoyer'    => 'decimal:2',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
