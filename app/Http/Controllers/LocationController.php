<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\BiensEnLocation;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locations = Location::with(['user', 'bienEnLocation.bien', 'mensualites'])
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
        $bienEnLocation = BiensEnLocation::findOrFail($request->bien_en_location_id);

        if ($bienEnLocation->bien->statut !== 'disponible') {
            return response()->json(['message' => 'Ce bien n\'est plus disponible à la location.'], 422);
        }

        $location = Location::create([
            'user_id'             => $request->user()->id,
            'bien_en_location_id' => $request->bien_en_location_id,
            'dateLocation'        => $request->dateLocation ?? now()->toDateString(),
        ]);

        $bienEnLocation->bien->update(['statut' => 'loue']);

        return response()->json([
            'message' => 'Location créée avec succès.',
            'data'    => new LocationResource($location->load(['user', 'bienEnLocation.bien'])),
        ], 201);
    }

    public function show(Location $location): JsonResponse
    {
        $this->authorize('view', $location);

        return response()->json(['data' => new LocationResource($location->load(['user', 'bienEnLocation.bien', 'mensualites']))]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $this->authorize('delete', $location);

        // Remettre le bien disponible
        $location->bienEnLocation->bien->update(['statut' => 'disponible']);
        $location->delete();

        return response()->json(['message' => 'Location supprimée.'], 204);
    }
}
