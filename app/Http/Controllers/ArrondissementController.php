<?php

namespace App\Http\Controllers;

use App\Models\Arrondissement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArrondissementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Arrondissement::orderBy('nom');

        if ($request->filled('ville_id')) {
            $query->where('ville_id', $request->integer('ville_id'));
        }

        return response()->json([
            'data' => $query->get(['id', 'nom', 'ville_id']),
        ]);
    }

    /**
     * Création d'un arrondissement — admin uniquement. Contrairement à
     * `ville`, pas d'unicité globale sur `nom` : deux villes différentes
     * peuvent tout à fait avoir un arrondissement de même nom.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'      => 'required|string|max:255',
            'ville_id' => 'required|exists:villes,id',
        ]);

        $arrondissement = Arrondissement::create($data);

        return response()->json(['data' => $arrondissement], 201);
    }

    public function update(Request $request, Arrondissement $arrondissement): JsonResponse
    {
        $data = $request->validate([
            'nom'      => 'required|string|max:255',
            'ville_id' => 'required|exists:villes,id',
        ]);

        $arrondissement->update($data);

        return response()->json(['data' => $arrondissement]);
    }

    public function destroy(Arrondissement $arrondissement): JsonResponse
    {
        $arrondissement->delete();

        return response()->json(['message' => 'Arrondissement supprimé.']);
    }
}
