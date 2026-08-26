<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            // Historique des demandes de certification d'un utilisateur — une
            // nouvelle ligne à chaque soumission (pas de flag unique sur
            // `users`) pour garder trace des refus/renvois, comme le fait déjà
            // `abonnements` pour les paiements. `User::estCertifie()` regarde
            // simplement la dernière ligne 'approuve'.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Chemins de stockage privés (disque 'local', PAS public_path) —
            // ce sont des pièces d'identité/documents sensibles, contrairement
            // aux photos de biens : jamais servies par une URL publique
            // devinable, uniquement via CertificationController::document,
            // qui vérifie que le demandeur est le propriétaire du dossier ou
            // un admin avant de streamer le fichier.
            $table->string('piece_identite_path');
            $table->string('registre_commerce_path');
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->text('commentaire_admin')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
