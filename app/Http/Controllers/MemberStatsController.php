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
        $biensQuery = Bien::with(['tofs', 'options', 'bienEnVente', 'bienEnLocation'])
            ->where('user_id', $user->id);

        // Appliquer les filtres si fournis
        if ($request->filled('statut')) {
            $biensQuery->where('statut', $request->statut);
        }

        $biens = $biensQuery->get();

        // Calculer les statistiques
        $totalBiens = $biens->count();
        $biensDisponibles = $biens->where('statut', 'disponible')->count();
        $biensLoues = $biens->where('statut', 'loué')->count();
        $biensEnVente = $biens->whereHas('bienEnVente')->count();
        $biensEnLocation = $biens->whereHas('bienEnLocation')->count();

        // Calculer la valeur totale
        $valeurTotale = $biens->sum('prix');
        $valeurVente = $biens->filter(fn($bien) => $bien->bienEnVente)->sum('prix');
        $revenusMensuels = $biens->filter(fn($bien) => $bien->bienEnLocation)
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
        
        $biens = Bien::with(['tofs', 'options', 'bienEnLocation.locations'])
            ->where('user_id', $user->id)
            ->where('statut', 'loué')
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
        
        $biens = Bien::with(['tofs', 'options', 'bienEnVente'])
            ->where('user_id', $user->id)
            ->whereHas('bienEnVente')
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
        
        $biens = Bien::with(['tofs', 'options', 'bienEnLocation'])
            ->where('user_id', $user->id)
            ->whereHas('bienEnLocation')
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
        
        $biensEnLocation = Bien::with(['bienEnLocation.locations'])
            ->where('user_id', $user->id)
            ->whereHas('bienEnLocation')
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
        
        $biens = Bien::with(['tofs', 'options', 'bienEnVente.achats', 'bienEnLocation.locations'])
            ->where('user_id', $user->id)
            ->get();

        $transactions = [];

        // Transactions de vente
        foreach ($biens as $bien) {
            if ($bien->bienEnVente && $bien->bienEnVente->achats) {
                foreach ($bien->bienEnVente->achats as $achat) {
                    $transactions[] = [
                        'id' => $achat->id,
                        'type' => 'vente',
                        'bien_titre' => $bien->titre,
                        'montant' => $achat->montant,
                        'date' => $achat->created_at,
                        'acheteur' => $achat->acheteur_nom ?? 'Non spécifié',
                    ];
                }
            }

            // Transactions de location
            if ($bien->bienEnLocation && $bien->bienEnLocation->locations) {
                foreach ($bien->bienEnLocation->locations as $location) {
                    $transactions[] = [
                        'id' => $location->id,
                        'type' => 'location',
                        'bien_titre' => $bien->titre,
                        'montant' => $location->montant,
                        'date' => $location->created_at,
                        'locataire' => $location->locataire_nom ?? 'Non spécifié',
                    ];
                }
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
