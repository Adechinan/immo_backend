<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Flux "mot de passe oublié" auto-service (par opposition à la
 * réinitialisation déclenchée par un admin — cf. CompteAdminController pour
 * le cas d'un compte piraté où l'utilisateur n'a plus accès à son email).
 *
 * Le lien de réinitialisation envoyé par email pointe vers le frontend web
 * (immo_frontend, cf. AppServiceProvider::boot) — cette page publique
 * appelle ensuite POST /reset-password avec le token reçu.
 */
class PasswordResetController extends Controller
{
    /**
     * Envoie le lien de réinitialisation par email. Toujours la même réponse
     * générique côté 200, que l'email existe ou non, pour ne pas révéler
     * quels emails ont un compte (énumération) — le broker Laravel gère déjà
     * ça correctement, on se contente de ne pas distinguer les statuts
     * ci-dessous dans la réponse HTTP.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Compte suspendu/désactivé : on ne débloque pas l'accès via ce
        // canal (ce serait un moyen de contourner une suspension). On répond
        // quand même un message générique — pas d'énumération de comptes.
        if ($user && ! $user->estActif()) {
            return response()->json([
                'message' => "Si un compte actif existe pour cet email, un lien de réinitialisation a été envoyé.",
            ]);
        }

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => "Si un compte actif existe pour cet email, un lien de réinitialisation a été envoyé.",
        ]);
    }

    /**
     * Valide le token et fixe le nouveau mot de passe. Révoque aussi toutes
     * les sessions Sanctum existantes du compte — même logique de sécurité
     * que CompteAdminController::reinitialiserMotDePasse : un changement de
     * mot de passe déconnecte partout.
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => "Lien invalide ou expiré. Redemandez un email de réinitialisation.",
            ], 422);
        }

        return response()->json(['message' => "Mot de passe réinitialisé. Vous pouvez vous connecter."]);
    }
}
