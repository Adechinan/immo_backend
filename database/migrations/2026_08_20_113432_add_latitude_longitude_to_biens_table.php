<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            // decimal(10,7)/(10,7) : précision suffisante pour une position
            // GPS (~1 cm) sans sur-dimensionner. Nullable : les biens créés
            // avant cette fonctionnalité (ou publiés sans pointer la carte)
            // n'ont pas de coordonnées — la fiche détail masque la carte
            // dans ce cas plutôt que d'afficher un pin par défaut trompeur.
            $table->decimal('latitude', 10, 7)->nullable()->after('codePostal');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
