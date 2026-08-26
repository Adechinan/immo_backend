<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbonnementController extends Controller
{
    /**
     * Statut d'abonnement du propriétaire connecté + montant du plan, pour
     * l'écran "Mon abonnement" côté mobile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $actif = $user->abonnementActif();

        return response()->json([
            'data' => [
                'actif'           => $actif !== null,
                'date_fin'        => $actif?->date_fin?->toDateString(),
                'montant_mensuel' => (int) config('services.abonnement.montant_mensuel'),
                'fedapay_public_key' => config('services.fedapay.public_key'),
            ],
        ]);
    }

    /**
     * Confirme le paiement de l'abonnement une fois le checkout FedaPay
     * terminé côté client. Contrairement au paiement de loyer (existant,
     * jamais vérifié côté serveur), on interroge ici l'API FedaPay avec la
     * clé secrète de la PLATEFORME (config('services.fedapay.secret_key'),
     * pas celle du propriétaire — c'est LoggyImmo qui encaisse l'abonnement)
     * pour confirmer que la transaction est bien "approved" et correspond
     * au montant attendu, avant d'activer l'accès. Un abonnement déjà actif
     * est prolongé de 30 jours à partir de sa date de fin actuelle plutôt
     * que depuis aujourd'hui (pas de jours "perdus" en renouvelant en avance).
     */
    public function confirmer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $secretKey = config('services.fedapay.secret_key');
        $baseUrl = rtrim(config('services.fedapay.base_url'), '/');
        $montant = (int) config('services.abonnement.montant_mensuel');

        if (empty($secretKey)) {
            Log::warning('Confirmation abonnement impossible : FEDAPAY_PRIVATE_KEY non configurée côté plateforme.');
            return response()->json(['message' => 'Paiement indisponible pour le moment. Réessayez plus tard.'], 503);
        }

        try {
            $response = Http::withToken($secretKey)
                ->get("{$baseUrl}/v1/transactions/{$data['transaction_id']}");
        } catch (\Throwable $e) {
            Log::error('Erreur appel API FedaPay (confirmation abonnement) : ' . $e->getMessage());
            return response()->json(['message' => "Impossible de vérifier le paiement auprès de FedaPay. Réessayez."], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => "Transaction FedaPay introuvable ou invalide."], 422);
        }

        // La forme exacte de la réponse ("v1/transaction" imbriqué ou objet
        // direct) varie selon les versions de l'API FedaPay — on tente les
        // deux plutôt que de supposer une seule structure, non testable ici
        // faute d'accès à un compte FedaPay réel dans cet environnement.
        $body = $response->json();
        $transaction = $body['v1/transaction'] ?? $body['transaction'] ?? $body['data'] ?? $body;

        $statut = $transaction['status'] ?? null;
        $montantPaye = (int) ($transaction['amount'] ?? 0);

        if ($statut !== 'approved') {
            return response()->json(['message' => "Le paiement n'a pas été approuvé (statut : {$statut})."], 422);
        }

        if ($montantPaye < $montant) {
            return response()->json(['message' => 'Montant payé insuffisant pour couvrir l\'abonnement.'], 422);
        }

        $user = $request->user();
        $actif = $user->abonnementActif();
        $debut = $actif?->date_fin ?? now();

        $abonnement = Abonnement::create([
            'user_id'                => $user->id,
            'montant'                => $montantPaye,
            'date_debut'             => $debut->toDateString(),
            'date_fin'               => $debut->copy()->addDays(30)->toDateString(),
            'statut'                 => 'actif',
            'fedapay_transaction_id' => $data['transaction_id'],
        ]);

        return response()->json([
            'message' => 'Abonnement activé avec succès.',
            'data'    => [
                'actif'    => true,
                'date_fin' => $abonnement->date_fin->toDateString(),
            ],
        ]);
    }
}
