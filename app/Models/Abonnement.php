<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'montant', 'date_debut', 'date_fin', 'statut', 'fedapay_transaction_id'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin'   => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estActif(): bool
    {
        // `endOfDay()` : un abonnement dont date_fin == aujourd'hui reste
        // valable jusqu'à la fin de la journée (date_fin seule, comparée à
        // `now()` qui a une heure, serait sinon considérée passée dès 00h00).
        return $this->statut === 'actif' && ! $this->date_fin->endOfDay()->isPast();
    }
}
