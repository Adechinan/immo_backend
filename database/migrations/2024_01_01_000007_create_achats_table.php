<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bien_en_vente_id')->constrained('biens_en_vente')->onDelete('cascade');
            $table->date('dateAchat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achats');
    }
};
