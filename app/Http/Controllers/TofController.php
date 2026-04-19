<?php

namespace App\Http\Controllers;

use App\Http\Resources\TofResource;
use App\Models\Bien;
use App\Models\Tof;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TofController extends Controller
{
    public function index(Bien $bien): JsonResponse
    {
        return response()->json(['data' => TofResource::collection($bien->tofs)]);
    }

    public function store(Request $request, Bien $bien): JsonResponse
    {
        $request->validate([
            'src' => 'required|string|max:2048',
        ]);

        $tof = $bien->tofs()->create(['src' => $request->src]);

        return response()->json([
            'message' => 'Photo ajoutée.',
            'data'    => new TofResource($tof),
        ], 201);
    }

    public function destroy(Bien $bien, Tof $tof): JsonResponse
    {
        abort_if($tof->bien_id !== $bien->id, 404);
        $tof->delete();

        return response()->json(['message' => 'Photo supprimée.'], 204);
    }
}
