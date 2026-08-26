<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            // Nullable : une ville peut être créée/importée avant que son
            // département ne soit renseigné, sans bloquer l'insertion.
            $table->foreignId('departement_id')->nullable()->after('nom')->constrained('departements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
        });
    }
};
