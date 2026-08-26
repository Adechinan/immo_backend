<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeRequest;
use App\Http\Resources\DemandeResource;
use App\Models\Bien;
use App\Models\Demande;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemandeController extends Controller
{
    public function __construct(private FirebaseNotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $demandes = Demande::with(['bien', 'user'])
            ->when($request->user()->type !== 'admin', fn ($q) => $q->whereHas('bien', fn ($b) => $b->where('user_id', $request->user()->id)))
            ->when($request->filled('bien_id'), fn($q) => $q->where('bien_id', $request->bien_id))
            // repondu=0 → uniquement les demandes en attente (utilisé pour le badge de la sidebar)
            ->when($request->filled('repondu'), fn ($q) => $request->boolean('repondu')
                ? $q->whereNotNull('repondu_at')
                : $q->whereNull('repondu_at'))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => DemandeResource::collection($demandes),
            'pagination' => [
                'total'        => $demandes->total(),
                'current_page' => $demandes->currentPage(),
                'last_page'    => $demandes->lastPage(),
            ],
        ]);
    }

    public function store(DemandeRequest $request, Bien $bien): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user('sanctum')?->id;

        $demande = $bien->demandes()->create($data);

        // Notifie le propriétaire du bien — silencieux si Firebase n'est pas
        // encore configuré (voir FirebaseNotificationService).
        if ($bien->user_id) {
            $bien->loadMissing('user');
            $this->notifications->sendToUser(
                $bien->user,
                'Nouvelle demande reçue',
                "Vous avez reçu une nouvelle demande pour \"{$bien->titre}\".",
                ['type' => 'demande', 'bien_id' => (string) $bien->id, 'demande_id' => (string) $demande->id],
            );
        }

        return response()->json([
            'message' => 'Demande envoyée avec succès.',
            'data'    => new DemandeResource($demande),
        ], 201);
    }

    /**
     * Demandes envoyées par le locataire/utilisateur connecté, avec les réponses reçues.
     */
    public function mesDemandes(Request $request): JsonResponse
    {
        $demandes = Demande::with('bien')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => DemandeResource::collection($demandes),
            'pagination' => [
                'total'        => $demandes->total(),
                'current_page' => $demandes->currentPage(),
                'last_page'    => $demandes->lastPage(),
            ],
        ]);
    }

    public function show(Demande $demande): JsonResponse
    {
        return response()->json(['data' => new DemandeResource($demande->load(['bien', 'user']))]);
    }

    public function repondre(Request $request, Demande $demande): JsonResponse
    {
        $demande->loadMissing(['bien', 'user']);

        if ($request->user()->type !== 'admin' && $demande->bien->user_id !== $request->user()->id) {
            abort(403, "Vous n'êtes pas autorisé à répondre à cette demande.");
        }

        $data = $request->validate(['reponse' => 'required|string']);

        $demande->update([
            'reponse'    => $data['reponse'],
            'repondu_at' => now(),
        ]);

        Mail::raw($data['reponse'], function ($message) use ($demande) {
            $message->to($demande->email_demandeur)
                ->subject("Réponse à votre demande concernant \"{$demande->bien->titre}\"");
        });

        return response()->json([
            'message' => 'Réponse envoyée avec succès.',
            'data'    => new DemandeResource($demande),
        ]);
    }

    public function destroy(Demande $demande): JsonResponse
    {
        $demande->delete();

        return response()->json(['message' => 'Demande supprimée.'], 204);
    }
}
