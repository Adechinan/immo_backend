<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrondissements', function (Blueprint $table) {
            $table->id();
            // Subdivision administrative d'une commune (ex. "1er Arrondissement"
            // à Cotonou). Purement optionnelle dans la hiérarchie : toutes les
            // communes n'ont pas leurs arrondissements renseignés ici, et un
            // quartier peut très bien être rattaché directement à sa ville
            // sans passer par un arrondissement (cf. quartiers.arrondissement_id
            // nullable).
            $table->string('nom');
            $table->foreignId('ville_id')->nullable()->constrained('villes')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nom', 'ville_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrondissements');
    }
};
