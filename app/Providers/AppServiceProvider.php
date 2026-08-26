<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le lien envoyé par la notification ResetPassword pointe vers le
        // frontend web (immo_frontend) plutôt que vers cette API — c'est là
        // que vit le formulaire "nouveau mot de passe", pas ici. Cf.
        // PasswordResetController::reset pour la vérification côté API.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return rtrim(config('app.frontend_url'), '/')
                . "/auth/reset-password?token={$token}&email={$email}";
        });
    }
}
