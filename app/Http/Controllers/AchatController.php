<?php

namespace App\Http\Controllers;

use App\Http\Requests\AchatRequest;
use App\Http\Resources\AchatResource;
use App\Models\Achat;
use App\Models\BiensEnVente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $achats = Achat::with(['user', 'bienEnVente.bien'])
            ->when($request->user()->type !== 'admin', fn($q) => $q->where('user_id', $request->user()->id))
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => AchatResource::collection($achats),
            'pagination' => [
                'total'        => $achats->total(),
                'current_page' => $achats->currentPage(),
                'last_page'    => $achats->lastPage(),
            ],
        ]);
    }

    public function store(AchatRequest $request): JsonResponse
    {
        $bienEnVente = BiensEnVente::findOrFail($request->bien_en_vente_id);

        // Vérifier que le bien est disponible
        if ($bienEnVente->bien->statut !== 'disponible') {
            return response()->json(['message' => 'Ce bien n\'est plus disponible.'], 422);
        }

        $achat = Achat::create([
            'user_id'          => $request->user()->id,
            'bien_en_vente_id' => $request->bien_en_vente_id,
            'dateAchat'        => $request->dateAchat ?? now()->toDateString(),
        ]);

        // Mettre à jour le statut du bien
        $bienEnVente->bien->update(['statut' => 'vendu']);

        return response()->json([
            'message' => 'Achat enregistré avec succès.',
            'data'    => new AchatResource($achat->load(['user', 'bienEnVente.bien'])),
        ], 201);
    }

    public function show(Achat $achat): JsonResponse
    {
        $this->authorize('view', $achat);

        return response()->json(['data' => new AchatResource($achat->load(['user', 'bienEnVente.bien']))]);
    }

    public function destroy(Achat $achat): JsonResponse
    {
        $this->authorize('delete', $achat);
        $achat->delete();

        return response()->json(['message' => 'Achat supprimé.'], 204);
    }
}
