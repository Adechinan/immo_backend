<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enregistrement des tokens FCM (un par appareil) pour l'envoi de
 * notifications push — cf. FirebaseNotificationService::sendToUser.
 */
class DeviceTokenController extends Controller
{
    /**
     * Enregistre (ou réassigne) le token FCM de l'appareil courant pour
     * l'utilisateur connecté. Idempotent : rappelable à chaque démarrage de
     * l'app sans créer de doublons (le token est unique en base).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => 'required|string|max:255',
            'platform' => 'nullable|string|in:android,ios',
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'  => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
            ],
        );

        return response()->json(['message' => 'Token enregistré.'], 201);
    }

    /**
     * Supprime le token de l'appareil courant — appelé à la déconnexion pour
     * qu'un téléphone partagé/réutilisé ne reçoive plus les notifications du
     * compte précédent.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string']);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'Token supprimé.'], 204);
    }
}
