<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('nom_manuel')->nullable()->after('user_id');
            $table->string('email_manuel')->nullable()->after('nom_manuel');
            $table->string('tel_manuel')->nullable()->after('email_manuel');
        });

        Schema::table('achats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('nom_manuel')->nullable()->after('user_id');
            $table->string('email_manuel')->nullable()->after('nom_manuel');
            $table->string('tel_manuel')->nullable()->after('email_manuel');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['nom_manuel', 'email_manuel', 'tel_manuel']);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('achats', function (Blueprint $table) {
            $table->dropColumn(['nom_manuel', 'email_manuel', 'tel_manuel']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
