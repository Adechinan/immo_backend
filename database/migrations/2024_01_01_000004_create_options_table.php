<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('bien_option', function (Blueprint $table) {
            $table->foreignId('bien_id')->constrained('biens')->onDelete('cascade');
            $table->foreignId('option_id')->constrained('options')->onDelete('cascade');
            $table->primary(['bien_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bien_option');
        Schema::dropIfExists('options');
    }
};
