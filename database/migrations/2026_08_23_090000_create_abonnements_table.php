<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            // Historique des paiements d'abonnement du propriétaire — un
            // abonnement "actif" est simplement le dernier dont `date_fin`
            // est encore dans le futur (cf. User::abonnementActif()), plutôt
            // qu'un flag global sur `users` : garde une trace de chaque
            // paiement et permet de recalculer la date d'expiration sans
            // rien perdre en cas de renouvellement.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->date('date_debut');
            $table->date('date_fin');
            // 'actif' tant que non explicitement annulé — l'expiration se
            // déduit de `date_fin < now()`, pas d'un job de passage à
            // 'expire' (évite un cron pour un simple calcul dérivable).
            $table->enum('statut', ['actif', 'annule'])->default('actif');
            // Référence de la transaction FedaPay (plateforme) vérifiée
            // côté serveur avant activation — cf. AbonnementController::confirmer.
            $table->string('fedapay_transaction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
