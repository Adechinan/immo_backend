<?php

namespace App\Http\Controllers;

use App\Http\Requests\AchatRequest;
use App\Http\Resources\AchatResource;
use App\Models\Achat;
use App\Models\Bien;
use App\Models\Statut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $achats = Achat::with(['user', 'bien.statut'])
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
        $bien = Bien::with('statut')->findOrFail($request->bien_id);

        // Vérifier que le bien est disponible à la vente
        if ($bien->statut->code !== 'a_vendre') {
            return response()->json(['message' => 'Ce bien n\'est plus disponible.'], 422);
        }

        $achat = Achat::create([
            'user_id'   => $request->user()->id,
            'bien_id'   => $request->bien_id,
            'dateAchat' => $request->dateAchat ?? now()->toDateString(),
        ]);

        // Mettre à jour le statut du bien
        $bien->update(['statut_id' => Statut::where('code', 'vendu')->value('id')]);

        return response()->json([
            'message' => 'Achat enregistré avec succès.',
            'data'    => new AchatResource($achat->load(['user', 'bien.statut'])),
        ], 201);
    }

    public function show(Achat $achat): JsonResponse
    {
        $this->authorize('view', $achat);

        return response()->json(['data' => new AchatResource($achat->load(['user', 'bien.statut']))]);
    }

    public function destroy(Achat $achat): JsonResponse
    {
        $this->authorize('delete', $achat);
        $achat->delete();

        return response()->json(['message' => 'Achat supprimé.'], 204);
    }
}
