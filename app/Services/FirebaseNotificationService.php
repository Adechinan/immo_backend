<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

/**
 * Envoi de notifications push via Firebase Cloud Messaging (API HTTP v1,
 * authentifiée par compte de service — la seule API FCM encore supportée
 * par Google, l'ancienne "legacy server key" a été retirée).
 *
 * Utilise directement le SDK `kreait/firebase-php` (pas le wrapper Laravel
 * `kreait/laravel-firebase`) : on évite ainsi de dépendre d'un fichier de
 * config publié par artisan (impossible à générer dans cet environnement
 * sans PHP/composer) — tout est piloté par `services.firebase.credentials`
 * (voir config/services.php), lui-même lu depuis FIREBASE_CREDENTIALS.
 *
 * Tant que ce chemin n'est pas configuré (avant que le projet Firebase ne
 * soit connecté), toute méthode se contente de logger un avertissement et
 * de ne rien envoyer — jamais d'exception qui casserait le flux appelant
 * (ex. DemandeController::store ne doit pas échouer si les notifs ne sont
 * pas encore branchées).
 */
class FirebaseNotificationService
{
    private ?\Kreait\Firebase\Contract\Messaging $messaging = null;
    private bool $unavailable = false;

    private function messaging(): ?\Kreait\Firebase\Contract\Messaging
    {
        if ($this->messaging !== null || $this->unavailable) {
            return $this->messaging;
        }

        $credentials = config('services.firebase.credentials');
        if (empty($credentials) || ! file_exists($credentials)) {
            Log::warning('FirebaseNotificationService: FIREBASE_CREDENTIALS non configuré ou introuvable — notification ignorée.');
            $this->unavailable = true;
            return null;
        }

        try {
            $this->messaging = (new Factory())->withServiceAccount($credentials)->createMessaging();
        } catch (\Throwable $e) {
            Log::error('FirebaseNotificationService: impossible d\'initialiser Firebase — ' . $e->getMessage());
            $this->unavailable = true;
            return null;
        }

        return $this->messaging;
    }

    /**
     * Envoie une notification à tous les appareils enregistrés d'un
     * utilisateur (potentiellement plusieurs — plusieurs téléphones/
     * réinstallations). Les tokens que Firebase signale comme invalides
     * (désinstallation, token expiré) sont supprimés de la base au passage,
     * pour ne pas les retenter indéfiniment.
     *
     * @param array<string, mixed> $data Payload additionnel (ex. type/route
     *                                   à ouvrir côté app au tap).
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();
        if (empty($tokens)) {
            return;
        }

        $messaging = $this->messaging();
        if ($messaging === null) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData(array_map('strval', $data));

        try {
            $report = $messaging->sendMulticast($message, $tokens);
            foreach ($report->invalidTokens() as $invalidToken) {
                DeviceToken::where('token', $invalidToken)->delete();
            }
        } catch (\Throwable $e) {
            Log::error('FirebaseNotificationService: échec de l\'envoi — ' . $e->getMessage());
        }
    }
}
