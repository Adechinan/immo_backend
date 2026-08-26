<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            // Nom de la commune (ex. "Cotonou", "Abomey-Calavi") — utilisé
            // tel quel comme valeur du champ `biens.ville`, donc pas de FK
            // stricte sur ce dernier (évite une migration lourde sur les
            // biens déjà en base ; cette table sert avant tout à peupler le
            // select mobile/web plutôt qu'à contraindre les données existantes).
            $table->string('nom')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villes');
    }
};
