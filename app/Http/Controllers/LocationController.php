<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Bien;
use App\Models\Location;
use App\Models\Statut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locations = Location::with(['user', 'bien.statut', 'mensualites'])
            ->when($request->user()->type !== 'admin', fn($q) => $q->where('user_id', $request->user()->id))
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => LocationResource::collection($locations),
            'pagination' => [
                'total'        => $locations->total(),
                'current_page' => $locations->currentPage(),
                'last_page'    => $locations->lastPage(),
            ],
        ]);
    }

    public function store(LocationRequest $request): JsonResponse
    {
        $bien = Bien::with('statut')->findOrFail($request->bien_id);

        if ($bien->statut->code !== 'a_louer') {
            return response()->json(['message' => 'Ce bien n\'est plus disponible à la location.'], 422);
        }

        $location = Location::create([
            'user_id'      => $request->user()->id,
            'bien_id'      => $request->bien_id,
            'dateLocation' => $request->dateLocation ?? now()->toDateString(),
        ]);

        $bien->update(['statut_id' => Statut::where('code', 'loue')->value('id')]);

        return response()->json([
            'message' => 'Location créée avec succès.',
            'data'    => new LocationResource($location->load(['user', 'bien.statut'])),
        ], 201);
    }

    public function show(Location $location): JsonResponse
    {
        $this->authorize('view', $location);

        return response()->json(['data' => new LocationResource($location->load(['user', 'bien.statut', 'mensualites']))]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $this->authorize('delete', $location);

        // Remettre le bien disponible à la location
        $location->bien->update(['statut_id' => Statut::where('code', 'a_louer')->value('id')]);
        $location->delete();

        return response()->json(['message' => 'Location supprimée.'], 204);
    }

    /**
     * Le propriétaire met fin au contrat de location en cours : le bien redevient
     * disponible à la location, mais l'historique (location + mensualités) est conservé.
     */
    public function terminer(Request $request, Location $location): JsonResponse
    {
        $location->loadMissing('bien');

        if ($location->bien->user_id !== $request->user()->id) {
            abort(403, "Vous n'êtes pas autorisé à gérer cette location.");
        }

        if ($location->date_fin) {
            return response()->json(['message' => 'Ce contrat de location est déjà terminé.'], 422);
        }

        $location->update(['date_fin' => now()->toDateString()]);
        $location->bien->update(['statut_id' => Statut::where('code', 'a_louer')->value('id')]);

        return response()->json([
            'message' => 'Le contrat de location a été terminé.',
            'data'    => new LocationResource($location->fresh()->load(['user', 'bien.statut'])),
        ]);
    }
}
