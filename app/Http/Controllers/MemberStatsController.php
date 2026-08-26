<?php

namespace App\Http\Controllers;

use App\Http\Resources\BienResource;
use App\Models\Bien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberStatsController extends Controller
{
    /**
     * Obtenir les statistiques des biens du membre connecté
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Récupérer tous les biens de l'utilisateur
        $biensQuery = Bien::with(['tofs', 'options', 'statut', 'locations.user', 'achats.user'])
            ->where('user_id', $user->id);

        // Appliquer les filtres si fournis
        if ($request->filled('statut')) {
            $biensQuery->statutCode($request->statut);
        }

        $biens = $biensQuery->get();

        // Calculer les statistiques
        $totalBiens = $biens->count();
        $biensDisponibles = $biens->whereIn('statut.code', ['a_vendre', 'a_louer'])->count();
        $biensLoues = $biens->where('statut.code', 'loue')->count();
        $biensEnVente = $biens->whereIn('statut.code', ['a_vendre', 'vendu'])->count();
        $biensEnLocation = $biens->whereIn('statut.code', ['a_louer', 'loue'])->count();

        // Calculer la valeur totale
        $valeurTotale = $biens->sum('prix');
        $valeurVente = $biens->whereIn('statut.code', ['a_vendre', 'vendu'])->sum('prix');
        $revenusMensuels = $biens->whereIn('statut.code', ['a_louer', 'loue'])
            ->sum('prix') / 12; // Estimation annuelle / 12

        // Taux d'occupation
        $tauxOccupation = $totalBiens > 0 ? round(($biensLoues / $totalBiens) * 100, 1) : 0;

        return response()->json([
            'stats' => [
                'total_biens' => $totalBiens,
                'biens_disponibles' => $biensDisponibles,
                'biens_loues' => $biensLoues,
                'biens_en_vente' => $biensEnVente,
                'biens_en_location' => $biensEnLocation,
                'valeur_totale' => $valeurTotale,
                'valeur_vente' => $valeurVente,
                'revenus_mensuels_estimes' => $revenusMensuels,
                'taux_occupation' => $tauxOccupation,
            ],
            'biens' => BienResource::collection($biens),
        ]);
    }

    /**
     * Obtenir les détails des biens loués
     */
    public function biensLoues(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $biens = Bien::with(['tofs', 'options', 'statut', 'locations.user', 'locations.mensualites'])
            ->where('user_id', $user->id)
            ->statutCode('loue')
            ->get();

        return response()->json([
            'data' => BienResource::collection($biens),
            'total' => $biens->count(),
        ]);
    }

    /**
     * Historique des biens vendus (avec l'acheteur)
     */
    public function biensVendus(Request $request): JsonResponse
    {
        $user = Auth::user();

        $biens = Bien::with(['tofs', 'options', 'statut', 'achats.user'])
            ->where('user_id', $user->id)
            ->statutCode('vendu')
            ->get();

        return response()->json([
            'data' => BienResource::collection($biens),
            'total' => $biens->count(),
        ]);
    }

    /**
     * Obtenir les détails des biens en vente
     */
    public function biensEnVente(Request $request): JsonResponse
    {
        $user = Auth::user();

        $biens = Bien::with(['tofs', 'options', 'statut'])
            ->where('user_id', $user->id)
            ->enVente()
            ->get();

        return response()->json([
            'data' => BienResource::collection($biens),
            'total' => $biens->count(),
        ]);
    }

    /**
     * Obtenir les détails des biens en location
     */
    public function biensEnLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        $biens = Bien::with(['tofs', 'options', 'statut'])
            ->where('user_id', $user->id)
            ->enLocation()
            ->get();

        return response()->json([
            'data' => BienResource::collection($biens),
            'total' => $biens->count(),
        ]);
    }

    /**
     * Obtenir les revenus mensuels estimés
     */
    public function revenusMensuels(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $biensEnLocation = Bien::with(['locations', 'statut'])
            ->where('user_id', $user->id)
            ->enLocation()
            ->get();

        $revenusMensuels = 0;
        $details = [];

        foreach ($biensEnLocation as $bien) {
            $loyerMensuel = $bien->prix / 12; // Prix annuel / 12
            $revenusMensuels += $loyerMensuel;
            
            $details[] = [
                'bien_id' => $bien->id,
                'titre' => $bien->titre,
                'loyer_mensuel' => $loyerMensuel,
                'statut' => $bien->statut,
            ];
        }

        return response()->json([
            'revenus_mensuels_totaux' => $revenusMensuels,
            'nombre_biens_location' => $biensEnLocation->count(),
            'details' => $details,
        ]);
    }

    /**
     * Obtenir l'historique des transactions
     */
    public function historiqueTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $biens = Bien::with(['achats.user', 'locations.user'])
            ->where('user_id', $user->id)
            ->get();

        $transactions = [];

        // Transactions de vente
        foreach ($biens as $bien) {
            foreach ($bien->achats as $achat) {
                $transactions[] = [
                    'id'              => $achat->id,
                    'type'            => 'vente',
                    'bien_id'         => $bien->id,
                    'bien_titre'      => $bien->titre,
                    'bien_adresse'    => $bien->adresse,
                    'bien_ville'      => $bien->ville,
                    'montant'         => $bien->prix,
                    'date'            => $achat->dateAchat ?? $achat->created_at,
                    'partie_prenante' => trim(($achat->user->prenom ?? '') . ' ' . ($achat->user->nom ?? '')) ?: 'Non spécifié',
                    'email'           => $achat->user->email ?? null,
                    'tel'             => $achat->user->tel   ?? null,
                ];
            }

            // Transactions de location
            foreach ($bien->locations as $location) {
                $transactions[] = [
                    'id'              => $location->id,
                    'type'            => 'location',
                    'bien_id'         => $bien->id,
                    'bien_titre'      => $bien->titre,
                    'bien_adresse'    => $bien->adresse,
                    'bien_ville'      => $bien->ville,
                    'montant'         => $bien->prix,
                    'date'            => $location->dateLocation ?? $location->created_at,
                    'partie_prenante' => trim(($location->user->prenom ?? '') . ' ' . ($location->user->nom ?? '')) ?: 'Non spécifié',
                    'email'           => $location->user->email ?? null,
                    'tel'             => $location->user->tel   ?? null,
                ];
            }
        }

        // Trier par date décroissante
        usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return response()->json([
            'data' => $transactions,
            'total' => count($transactions),
        ]);
    }
}
