<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table standard attendue par le broker Laravel (Password::sendResetLink /
 * Password::reset, config/auth.php > passwords.users.table) — absente du
 * schéma initial de ce projet, qui n'avait jamais eu de flux "mot de passe
 * oublié" avant l'ajout de PasswordResetController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
