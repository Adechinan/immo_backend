<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->decimal('surface', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('pieces')->nullable()->default(1);
            $table->integer('chambres')->nullable()->default(0);
            $table->integer('etage')->nullable();
            $table->string('adresse');
            $table->string('ville');
            $table->string('codePostal', 10)->nullable();
            $table->foreignId('statut_id')->constrained('statuts');
            $table->decimal('prix', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
