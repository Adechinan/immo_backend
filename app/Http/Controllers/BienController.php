<?php

namespace App\Http\Controllers;

use App\Http\Requests\BienRequest;
use App\Http\Resources\BienResource;
use App\Models\Bien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BienController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bien::with(['tofs', 'options', 'bienEnVente', 'bienEnLocation']);

        // Filtres
        if ($request->filled('ville')) {
            $query->ville($request->ville);
        }
        if ($request->filled('prix_min')) {
            $query->prixMin((float) $request->prix_min);
        }
        if ($request->filled('prix_max')) {
            $query->prixMax((float) $request->prix_max);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type')) {
            if ($request->type === 'vente') {
                $query->enVente();
            } elseif ($request->type === 'location') {
                $query->enLocation();
            }
        }
        if ($request->filled('chambres')) {
            $query->where('chambres', '>=', $request->chambres);
        }
        if ($request->filled('surface_min')) {
            $query->where('surface', '>=', $request->surface_min);
        }

        $biens = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => BienResource::collection($biens),
            'pagination' => [
                'total'        => $biens->total(),
                'per_page'     => $biens->perPage(),
                'current_page' => $biens->currentPage(),
                'last_page'    => $biens->lastPage(),
            ],
        ]);
    }

    public function store(BienRequest $request): JsonResponse
    {
        $bien = Bien::create($request->validated());

        // Attacher les options si fournies
        if ($request->filled('options')) {
            $bien->options()->sync($request->options);
        }

        // Créer l'entrée BiensEnVente ou BiensEnLocation selon le type
        if ($request->filled('type_bien')) {
            if ($request->type_bien === 'vente') {
                $bien->bienEnVente()->create();
            } elseif ($request->type_bien === 'location') {
                $bien->bienEnLocation()->create();
            }
        }

        return response()->json([
            'message' => 'Bien créé avec succès.',
            'data'    => new BienResource($bien->load(['tofs', 'options', 'bienEnVente', 'bienEnLocation'])),
        ], 201);
    }

    public function show(Bien $bien): JsonResponse
    {
        $bien->load(['tofs', 'options', 'bienEnVente.achats', 'bienEnLocation.locations', 'demandes']);

        return response()->json(['data' => new BienResource($bien)]);
    }

    public function update(BienRequest $request, Bien $bien): JsonResponse
    {
        $bien->update($request->validated());

        if ($request->filled('options')) {
            $bien->options()->sync($request->options);
        }

        return response()->json([
            'message' => 'Bien mis à jour avec succès.',
            'data'    => new BienResource($bien->load(['tofs', 'options'])),
        ]);
    }

    public function destroy(Bien $bien): JsonResponse
    {
        $bien->delete();

        return response()->json(['message' => 'Bien supprimé avec succès.'], 204);
    }

    public function dernieresAnnonces(): JsonResponse
    {
        $biens = Bien::with(['tofs', 'options', 'bienEnVente', 'bienEnLocation'])
            ->where('statut', 'disponible')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return response()->json([
            'data' => $biens,
            'total' => $biens->count(),
        ]);
    }
}
