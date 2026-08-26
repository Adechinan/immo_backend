<?php

namespace App\Http\Controllers;

use App\Models\Statut;
use Illuminate\Http\JsonResponse;

class StatutController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Statut::all(['id', 'code', 'libelle', 'couleur'])]);
    }
}
