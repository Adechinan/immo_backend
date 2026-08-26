<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gestion admin des comptes utilisateurs : changement de statut
 * (actif/suspendu/désactivé), révocation des sessions, et réinitialisation
 * de mot de passe — procédure de récupération d'un compte piraté.
 *
 * Toutes les routes de ce contrôleur sont derrière le middleware `admin`
 * (cf. routes/api.php), donc `$request->user()` ici est toujours un admin.
 */
class CompteAdminController extends Controller
{
    /**
     * Liste complète des comptes (pattern client-side filter/pagination déjà
     * utilisé pour villes/arrondissements/quartiers côté admin) — pas de
     * pagination serveur, l'admin filtre/pagine en mémoire.
     */
    public function index(): JsonResponse
    {
        $users = User::orderBy('nom')->get()->map(fn (User $u) => $this->presenter($u));

        return response()->json(['data' => $users]);
    }

    /**
     * Change le statut d'un compte (actif / suspendu / desactive).
     * Dès que le nouveau statut n'est pas "actif", tous les tokens Sanctum
     * du compte sont révoqués : l'utilisateur est déconnecté immédiatement
     * de toutes ses sessions (web, mobile), pas seulement empêché de se
     * reconnecter.
     */
    public function changerStatut(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'statut' => 'required|in:actif,suspendu,desactive',
            'motif'  => 'nullable|string|max:255',
        ]);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => "Vous ne pouvez pas modifier le statut de votre propre compte."], 422);
        }

        if ($user->type === 'admin' && $data['statut'] !== 'actif') {
            return response()->json(['message' => "Impossible de suspendre ou désactiver un compte administrateur."], 422);
        }

        $user->update([
            'statut'            => $data['statut'],
            'statut_motif'      => $data['motif'] ?? null,
            'statut_updated_at' => now(),
        ]);

        if ($data['statut'] !== 'actif') {
            $user->tokens()->delete();
        }

        return response()->json(['data' => $this->presenter($user->fresh())]);
    }

    /**
     * Déconnecte le compte de toutes ses sessions sans changer son statut —
     * première étape recommandée dès qu'un piratage est suspecté, avant même
     * de décider s'il faut suspendre le compte ou juste réinitialiser le mot
     * de passe.
     */
    public function revoquerSessions(Request $request, User $user): JsonResponse
    {
        $nb = $user->tokens()->count();
        $user->tokens()->delete();

        return response()->json(['message' => "{$nb} session(s) révoquée(s)."]);
    }

    /**
     * Réinitialise le mot de passe avec une valeur temporaire générée
     * aléatoirement et révoque toutes les sessions actives — procédure de
     * récupération d'un compte piraté quand l'utilisateur n'a plus accès à
     * son email (donc pas de lien de réinitialisation possible) ou quand
     * l'admin veut couper l'accès immédiatement. Le mot de passe temporaire
     * est renvoyé une seule fois dans la réponse : à communiquer à
     * l'utilisateur par un canal vérifié (téléphone, en personne), jamais
     * par le même canal potentiellement compromis.
     */
    public function reinitialiserMotDePasse(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => "Utilisez votre propre écran de compte pour changer votre mot de passe."], 422);
        }

        $motDePasseTemporaire = Str::password(12, symbols: false);

        $user->update(['password' => Hash::make($motDePasseTemporaire)]);
        $user->tokens()->delete();

        return response()->json([
            'message'                => "Mot de passe réinitialisé et sessions révoquées.",
            'mot_de_passe_temporaire' => $motDePasseTemporaire,
        ]);
    }

    private function presenter(User $user): array
    {
        return [
            'id'                 => $user->id,
            'prenom'             => $user->prenom,
            'nom'                => $user->nom,
            'email'              => $user->email,
            'tel'                => $user->tel,
            'type'               => $user->type,
            'statut'             => $user->statut,
            'statut_motif'       => $user->statut_motif,
            'statut_updated_at'  => $user->statut_updated_at,
            'created_at'         => $user->created_at,
        ];
    }
}
