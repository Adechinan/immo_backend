<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FedapayCompteController extends Controller
{
    /**
     * État du compte FedaPay du propriétaire connecté — ne renvoie jamais la
     * clé secrète (cachée par `User::$hidden`), seulement la clé publique et
     * un booléen indiquant si une clé secrète est enregistrée.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'fedapay_public_key' => $user->fedapay_public_key,
                'has_secret_key'     => ! empty($user->getRawOriginal('fedapay_secret_key')),
            ],
        ]);
    }

    /**
     * Enregistre/modifie les clés FedaPay du propriétaire connecté. La clé
     * secrète est optionnelle à la mise à jour (permet de changer la clé
     * publique seule sans re-saisir la secrète) — mais obligatoire à la
     * toute première configuration, sinon aucun paiement ne pourrait être
     * initié pour son compte.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'fedapay_public_key' => 'required|string|starts_with:pk_|max:255',
            'fedapay_secret_key' => ($user->fedapay_secret_key ? 'nullable' : 'required') . '|string|starts_with:sk_|max:255',
        ]);

        $user->fedapay_public_key = $data['fedapay_public_key'];
        if (! empty($data['fedapay_secret_key'])) {
            $user->fedapay_secret_key = $data['fedapay_secret_key'];
        }
        $user->save();

        return response()->json([
            'message' => 'Compte FedaPay mis à jour avec succès.',
            'data'    => [
                'fedapay_public_key' => $user->fedapay_public_key,
                'has_secret_key'     => ! empty($user->getRawOriginal('fedapay_secret_key')),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['fedapay_public_key' => null, 'fedapay_secret_key' => null]);

        return response()->json(['message' => 'Compte FedaPay retiré.'], 200);
    }
}
