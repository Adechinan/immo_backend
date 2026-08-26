<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\JsonResponse;

class DepartementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Departement::orderBy('nom')->get(['id', 'nom']),
        ]);
    }
}
