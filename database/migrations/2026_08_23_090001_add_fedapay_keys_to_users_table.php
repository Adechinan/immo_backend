<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Clés du compte FedaPay propre à chaque propriétaire, pour que
            // les paiements de ses locataires/acheteurs créditent directement
            // son compte à lui plutôt que celui de la plateforme (dont les
            // clés restent en config/services.php, utilisées uniquement pour
            // le paiement de l'abonnement lui-même). La clé publique n'a pas
            // besoin d'être chiffrée (destinée à être envoyée au client pour
            // le widget de paiement) ; la clé secrète l'est.
            $table->string('fedapay_public_key')->nullable()->after('google_id');
            $table->text('fedapay_secret_key')->nullable()->after('fedapay_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fedapay_public_key', 'fedapay_secret_key']);
        });
    }
};
