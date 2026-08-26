<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statut du compte, distinct de `type` (rôle) :
     *  - actif      : accès normal (défaut).
     *  - suspendu   : blocage réversible décidé par l'admin (ex. signalement
     *                 en cours d'examen) — même traitement que "desactive"
     *                 côté connexion, mais sémantique différente pour l'UI.
     *  - desactive  : compte fermé par l'admin (ex. abus confirmé, ou
     *                 fermeture demandée par l'utilisateur).
     * Les deux statuts non "actif" bloquent /login et /auth/google, et
     * révoquent immédiatement tous les tokens Sanctum existants (cf.
     * CompteAdminController::changerStatut) — l'utilisateur est donc
     * déconnecté partout, pas seulement empêché de se reconnecter.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['actif', 'suspendu', 'desactive'])->default('actif')->after('type');
            $table->string('statut_motif')->nullable()->after('statut');
            $table->timestamp('statut_updated_at')->nullable()->after('statut_motif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['statut', 'statut_motif', 'statut_updated_at']);
        });
    }
};
