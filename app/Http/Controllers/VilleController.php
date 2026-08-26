<?php

namespace App\Http\Controllers;

use App\Models\Ville;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VilleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            // `departement` chargé (id + nom) pour permettre un select en
            // cascade département → ville côté client sans requête
            // supplémentaire.
            'data' => Ville::with('departement:id,nom')->orderBy('nom')->get(['id', 'nom', 'departement_id']),
        ]);
    }

    /**
     * Création d'une ville — admin uniquement (cf. routes/api.php, groupe
     * `admin`). Les départements béninois sont fixes (12, déjà seedés) donc
     * pas d'endpoint de création pour `departement_id` : l'admin choisit
     * parmi ceux existants.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'            => 'required|string|max:255|unique:villes,nom',
            'departement_id' => 'required|exists:departements,id',
        ]);

        $ville = Ville::create($data);

        return response()->json(['data' => $ville->load('departement:id,nom')], 201);
    }

    public function update(Request $request, Ville $ville): JsonResponse
    {
        $data = $request->validate([
            'nom'            => 'required|string|max:255|unique:villes,nom,' . $ville->id,
            'departement_id' => 'required|exists:departements,id',
        ]);

        $ville->update($data);

        return response()->json(['data' => $ville->load('departement:id,nom')]);
    }

    public function destroy(Ville $ville): JsonResponse
    {
        $ville->delete();

        return response()->json(['message' => 'Ville supprimée.']);
    }
}
