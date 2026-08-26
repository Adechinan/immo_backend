<?php

namespace App\Http\Controllers;

use App\Models\Quartier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuartierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Quartier::orderBy('nom');

        if ($request->filled('ville_id')) {
            $query->where('ville_id', $request->integer('ville_id'));
        }

        if ($request->filled('arrondissement_id')) {
            $query->where('arrondissement_id', $request->integer('arrondissement_id'));
        }

        return response()->json([
            'data' => $query->get(['id', 'nom', 'ville_id', 'arrondissement_id']),
        ]);
    }

    /**
     * Création d'un quartier — admin uniquement. `arrondissement_id` reste
     * optionnel (cf. migration) : un quartier peut être rattaché directement
     * à sa ville sans arrondissement connu.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'               => 'required|string|max:255',
            'ville_id'          => 'required|exists:villes,id',
            'arrondissement_id' => 'nullable|exists:arrondissements,id',
        ]);

        $quartier = Quartier::create($data);

        return response()->json(['data' => $quartier], 201);
    }

    public function update(Request $request, Quartier $quartier): JsonResponse
    {
        $data = $request->validate([
            'nom'               => 'required|string|max:255',
            'ville_id'          => 'required|exists:villes,id',
            'arrondissement_id' => 'nullable|exists:arrondissements,id',
        ]);

        $quartier->update($data);

        return response()->json(['data' => $quartier]);
    }

    public function destroy(Quartier $quartier): JsonResponse
    {
        $quartier->delete();

        return response()->json(['message' => 'Quartier supprimé.']);
    }
}
