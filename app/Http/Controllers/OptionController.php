<?php

namespace App\Http\Controllers;

use App\Http\Resources\OptionResource;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => OptionResource::collection(Option::all())]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|unique:options,name']);

        $option = Option::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Option créée.',
            'data'    => new OptionResource($option),
        ], 201);
    }

    public function update(Request $request, Option $option): JsonResponse
    {
        $request->validate(['name' => 'required|string|unique:options,name,' . $option->id]);
        $option->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Option mise à jour.',
            'data'    => new OptionResource($option),
        ]);
    }

    public function destroy(Option $option): JsonResponse
    {
        $option->delete();

        return response()->json(['message' => 'Option supprimée.'], 204);
    }
}
