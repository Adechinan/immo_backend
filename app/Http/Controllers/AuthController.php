<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'prenom'   => $request->prenom,
            'nom'      => $request->nom,
            'tel'      => $request->tel,
            'email'    => $request->email,
            'type'     => $request->type || 'user',
            'password' => Hash::make($request->password),
        ]);

        // Assigner le rôle par défaut 'user'
        $user->assignRole($request->type || 'user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie.',
            'user'    => new UserResource($user),
            'token'   => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->estActif()) {
            return response()->json([
                'message' => $this->messageCompteBloque($user),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user'    => new UserResource($user),
            'token'   => $token,
        ]);
    }

    /**
     * Message affiché quand un compte non actif tente de se connecter
     * (login classique ou Google) — distingue suspension (temporaire) et
     * désactivation (fermeture), et rappelle le motif s'il a été renseigné
     * par l'admin lors du changement de statut.
     */
    private function messageCompteBloque(User $user): string
    {
        $base = $user->statut === 'suspendu'
            ? "Ce compte est suspendu."
            : "Ce compte a été désactivé.";

        $motif = $user->statut_motif ? " Motif : {$user->statut_motif}." : '';

        return "{$base}{$motif} Contactez l'équipe RESILIA pour plus d'informations.";
    }

    /**
     * "Se connecter avec Google" — web (Google Identity Services) et mobile
     * (package google_sign_in) envoient tous les deux un id_token JWT signé
     * par Google ; on le vérifie via l'endpoint tokeninfo de Google (pas de
     * dépendance supplémentaire type google/apiclient), puis on
     * crée/retrouve le compte par email et on émet un token Sanctum, comme
     * pour /login et /register.
     */
    public function google(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return response()->json(['message' => "Connexion Google non configurée côté serveur."], 500);
        }

        $response = Http::withOptions([
            // PHP/cURL sur ce poste n'a pas de bundle de certificats racine
            // configuré (php.ini curl.cainfo absent) → "SSL certificate
            // ... unable to get local issuer certificate" sur TOUT appel
            // HTTPS sortant. On désactive donc la vérification uniquement en
            // local le temps de corriger ça proprement côté PHP (voir
            // immo_backend/README.md) — ne jamais désactiver en production.
            'verify' => !app()->isLocal(),
        ])->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages(['id_token' => "Jeton Google invalide ou expiré."]);
        }

        $payload = $response->json();

        // Le "aud" doit correspondre à NOTRE client_id, sinon n'importe quel
        // id_token Google valide pour une autre appli pourrait être rejoué ici.
        if (($payload['aud'] ?? null) !== $clientId) {
            throw ValidationException::withMessages(['id_token' => "Jeton Google émis pour une autre application."]);
        }

        $email = $payload['email'] ?? null;
        if (!$email || ($payload['email_verified'] ?? 'false') !== 'true') {
            throw ValidationException::withMessages(['id_token' => "Email Google introuvable ou non vérifié."]);
        }

        $googleId = $payload['sub'];

        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if ($user) {
            // Compte déjà créé via email/mot de passe : on le relie à Google.
            if (!$user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        } else {
            $user = User::create([
                'prenom'    => $payload['given_name'] ?? explode(' ', $payload['name'] ?? 'Utilisateur')[0],
                'nom'       => $payload['family_name'] ?? '',
                'email'     => $email,
                'google_id' => $googleId,
                'password'  => null,
                'type'      => 'user',
            ]);
            $user->assignRole('user');
        }

        if (!$user->estActif()) {
            return response()->json([
                'message' => $this->messageCompteBloque($user),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion Google réussie.',
            'user'    => new UserResource($user),
            'token'   => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
