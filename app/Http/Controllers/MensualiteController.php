<?php

namespace App\Http\Controllers;

use App\Http\Requests\MensualiteRequest;
use App\Http\Resources\MensualiteResource;
use App\Models\Location;
use App\Models\Mensualite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MensualiteController extends Controller
{
    public function index(Location $location): JsonResponse
    {
        $this->authorize('view', $location);

        $mensualites = $location->mensualites()->orderBy('datePaiement', 'desc')->get();

        return response()->json(['data' => MensualiteResource::collection($mensualites)]);
    }

    public function store(MensualiteRequest $request, Location $location): JsonResponse
    {
        $this->authorize('update', $location);

        $mensualite = $location->mensualites()->create($request->validated());

        return response()->json([
            'message' => 'Mensualité enregistrée.',
            'data'    => new MensualiteResource($mensualite),
        ], 201);
    }

    public function show(Location $location, Mensualite $mensualite): JsonResponse
    {
        $this->authorize('view', $location);

        return response()->json(['data' => new MensualiteResource($mensualite)]);
    }

    public function update(MensualiteRequest $request, Location $location, Mensualite $mensualite): JsonResponse
    {
        $this->authorize('update', $location);
        $mensualite->update($request->validated());

        return response()->json([
            'message' => 'Mensualité mise à jour.',
            'data'    => new MensualiteResource($mensualite),
        ]);
    }

    public function destroy(Location $location, Mensualite $mensualite): JsonResponse
    {
        $this->authorize('delete', $location);
        $mensualite->delete();

        return response()->json(['message' => 'Mensualité supprimée.'], 204);
    }
}
