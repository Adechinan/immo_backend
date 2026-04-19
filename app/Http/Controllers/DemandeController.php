<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeRequest;
use App\Http\Resources\DemandeResource;
use App\Models\Bien;
use App\Models\Demande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemandeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $demandes = Demande::with('bien')
            ->when($request->filled('bien_id'), fn($q) => $q->where('bien_id', $request->bien_id))
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
        $demande = $bien->demandes()->create($request->validated());

        return response()->json([
            'message' => 'Demande envoyée avec succès.',
            'data'    => new DemandeResource($demande),
        ], 201);
    }

    public function show(Demande $demande): JsonResponse
    {
        return response()->json(['data' => new DemandeResource($demande->load('bien'))]);
    }

    public function destroy(Demande $demande): JsonResponse
    {
        $demande->delete();

        return response()->json(['message' => 'Demande supprimée.'], 204);
    }
}
