<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            // Quartier/village au sein d'une commune (ex. "Fidjrossè" à
            // Cotonou). Rattaché directement à sa ville (comme pour
            // départements → villes), et optionnellement à un arrondissement
            // quand celui-ci est connu — l'arrondissement n'est qu'un niveau
            // intermédiaire facultatif, pas une obligation de saisie.
            $table->string('nom');
            $table->foreignId('ville_id')->nullable()->constrained('villes')->nullOnDelete();
            $table->foreignId('arrondissement_id')->nullable()->constrained('arrondissements')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nom', 'ville_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quartiers');
    }
};
