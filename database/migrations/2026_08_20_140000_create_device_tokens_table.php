<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Le token FCM lui-même — unique tout court (pas juste par user) : un
            // même appareil ne doit apparaître qu'une fois, et si l'utilisateur se
            // déconnecte/reconnecte avec un autre compte sur le même téléphone, on
            // veut que le token soit réassigné au nouveau user plutôt que dupliqué.
            $table->string('token')->unique();
            // 'android' | 'ios' — pas encore exploité côté envoi (l'API HTTP v1 de
            // Firebase gère les deux de la même façon), mais utile pour du debug/
            // futures notifications spécifiques à une plateforme.
            $table->string('platform')->default('android');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
