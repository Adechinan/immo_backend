<?php

namespace App\Http\Controllers;

use App\Http\Resources\BienResource;
use App\Http\Resources\UserResource;
use App\Models\Bien;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs de l'app (pour un propriétaire choisissant un locataire/acheteur existant).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::where('id', '!=', $request->user()->id)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(function ($w) use ($q) {
                    $w->where('prenom', 'ilike', "%{$q}%")
                        ->orWhere('nom', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('prenom')
            ->limit(50)
            ->get();

        return response()->json(['data' => UserResource::collection($users)]);
    }

    /**
     * Profil public d'un publicateur : ses informations, des compteurs par statut
     * (pour les boutons de filtre) et ses biens publiés (paginés, filtrables),
     * du plus récent au plus ancien. Accessible sans authentification (consulté
     * depuis une fiche bien).
     */
    public function publicProfile(Request $request, User $user): JsonResponse
    {
        $stats = [
            'total'    => Bien::where('user_id', $user->id)->count(),
            'a_louer'  => Bien::where('user_id', $user->id)->statutCode('a_louer')->count(),
            'loue'     => Bien::where('user_id', $user->id)->statutCode('loue')->count(),
            'a_vendre' => Bien::where('user_id', $user->id)->statutCode('a_vendre')->count(),
            'vendu'    => Bien::where('user_id', $user->id)->statutCode('vendu')->count(),
        ];

        $query = Bien::with(['tofs', 'options', 'statut'])
            ->where('user_id', $user->id);

        if ($request->filled('statut')) {
            $query->statutCode($request->statut);
        }
        if ($request->filled('type')) {
            if ($request->type === 'vente') {
                $query->enVente();
            } elseif ($request->type === 'location') {
                $query->enLocation();
            }
        }
        if ($request->filled('prix_min')) {
            $query->prixMin((float) $request->prix_min);
        }
        if ($request->filled('prix_max')) {
            $query->prixMax((float) $request->prix_max);
        }
        if ($request->filled('pieces')) {
            $query->where('pieces', '>=', $request->pieces);
        }
        if ($request->filled('chambres')) {
            $query->where('chambres', '>=', $request->chambres);
        }
        if ($request->filled('surface_min')) {
            $query->where('surface', '>=', $request->surface_min);
        }

        $biens = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 9));

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'stats' => $stats,
                'biens' => BienResource::collection($biens),
                'pagination' => [
                    'total'        => $biens->total(),
                    'per_page'     => $biens->perPage(),
                    'current_page' => $biens->currentPage(),
                    'last_page'    => $biens->lastPage(),
                ],
            ],
        ]);
    }
}
