<?php

use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\AchatController;
use App\Http\Controllers\ArrondissementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\CompteAdminController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\FedapayCompteController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MemberStatsController;
use App\Http\Controllers\MensualiteController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\QuartierController;
use App\Http\Controllers\StatutController;
use App\Http\Controllers\TofController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VilleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Immobilier Laravel 12
|--------------------------------------------------------------------------
*/

// --- Auth (public) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
// Mot de passe oublié — throttlé à part (envoi d'email/appel au broker à
// chaque requête), comme /biens/recherche-ia pour la même raison de coût.
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
Route::post('/reset-password',  [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

// --- Public ---
Route::get('/biens',            [BienController::class, 'index']);
Route::get('/biens/dernieres-annonces', [BienController::class, 'dernieresAnnonces']);
// Recherche en langage naturel ("un logement à Womey, budget 20 000") — public
// comme /biens, mais throttlée à part : chaque appel coûte un appel API
// Anthropic, contrairement aux autres routes publiques qui ne touchent que la
// base de données.
Route::post('/biens/recherche-ia', [BienController::class, 'rechercheIA'])->middleware('throttle:20,1');
Route::get('/biens/{bien}',     [BienController::class, 'show'])->whereNumber('bien');
Route::get('/biens/{bien}/tofs',[TofController::class, 'index'])->whereNumber('bien');
Route::get('/options',          [OptionController::class, 'index']);
Route::get('/statuts',          [StatutController::class, 'index']);
Route::get('/villes',           [VilleController::class, 'index']);
Route::get('/departements',     [DepartementController::class, 'index']);
Route::get('/arrondissements',  [ArrondissementController::class, 'index']);
Route::get('/quartiers',        [QuartierController::class, 'index']);
// Clé publique + disponibilité du paiement pour un bien donné — public (le
// locataire/acheteur potentiel n'a pas forcément de compte) : cf.
// BienController::paiementInfo.
Route::get('/biens/{bien}/paiement-info', [BienController::class, 'paiementInfo'])->whereNumber('bien');
Route::get('/publicateurs/{user}', [UserController::class, 'publicProfile'])->whereNumber('user');

// Demandes (envoi sans authentification)
Route::post('/biens/{bien}/demandes', [DemandeController::class, 'store'])->whereNumber('bien');

// --- Authentifié ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Biens (CRUD — admin/propriétaire)
    Route::post('/biens',              [BienController::class, 'store']);
    Route::put('/biens/{bien}',        [BienController::class, 'update'])->whereNumber('bien');
    Route::patch('/biens/{bien}',      [BienController::class, 'update'])->whereNumber('bien');
    Route::delete('/biens/{bien}',     [BienController::class, 'destroy'])->whereNumber('bien');
    Route::post('/biens/{bien}/assigner', [BienController::class, 'assignerOccupant'])->whereNumber('bien');

    // Utilisateurs (pour assigner un locataire/acheteur existant)
    Route::get('/users', [UserController::class, 'index']);

    // Notifications push — token FCM de l'appareil courant
    Route::post('/device-tokens',   [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    // Abonnement propriétaire (accès à la réception de paiement)
    Route::get('/abonnement',            [AbonnementController::class, 'show']);
    Route::post('/abonnement/confirmer', [AbonnementController::class, 'confirmer']);

    // Compte FedaPay personnel du propriétaire
    Route::get('/fedapay-compte',    [FedapayCompteController::class, 'show']);
    Route::put('/fedapay-compte',    [FedapayCompteController::class, 'update']);
    Route::delete('/fedapay-compte', [FedapayCompteController::class, 'destroy']);

    // Tofs
    Route::post('/biens/{bien}/tofs',          [TofController::class, 'store'])->whereNumber('bien');
    Route::delete('/biens/{bien}/tofs/{tof}',  [TofController::class, 'destroy'])->whereNumber('bien')->whereNumber('tof');

    // Options (admin)
    Route::post('/options',              [OptionController::class, 'store']);
    Route::put('/options/{option}',      [OptionController::class, 'update']);
    Route::delete('/options/{option}',   [OptionController::class, 'destroy']);

    // Achats
    Route::get('/achats',          [AchatController::class, 'index']);
    Route::post('/achats',         [AchatController::class, 'store']);
    Route::get('/achats/{achat}',  [AchatController::class, 'show']);
    Route::delete('/achats/{achat}', [AchatController::class, 'destroy']);

    // Locations
    Route::get('/locations',              [LocationController::class, 'index']);
    Route::post('/locations',             [LocationController::class, 'store']);
    Route::get('/locations/{location}',   [LocationController::class, 'show']);
    Route::post('/locations/{location}/terminer', [LocationController::class, 'terminer']);
    Route::delete('/locations/{location}',[LocationController::class, 'destroy']);

    // Mensualités (imbriquées dans Location)
    Route::get('/locations/{location}/mensualites',                    [MensualiteController::class, 'index']);
    Route::post('/locations/{location}/mensualites',                   [MensualiteController::class, 'store']);
    Route::get('/locations/{location}/mensualites/{mensualite}',       [MensualiteController::class, 'show']);
    Route::put('/locations/{location}/mensualites/{mensualite}',       [MensualiteController::class, 'update']);
    Route::delete('/locations/{location}/mensualites/{mensualite}',    [MensualiteController::class, 'destroy']);

    // Demandes (consultation propriétaire/admin)
    Route::get('/demandes',           [DemandeController::class, 'index']);
    Route::get('/mes-demandes',       [DemandeController::class, 'mesDemandes']);
    Route::get('/demandes/{demande}', [DemandeController::class, 'show']);
    Route::post('/demandes/{demande}/repondre', [DemandeController::class, 'repondre']);
    Route::delete('/demandes/{demande}', [DemandeController::class, 'destroy']);

    // Certification de compte (badge de confiance — soumission)
    Route::get('/certifications/moi',   [CertificationController::class, 'moi']);
    Route::post('/certifications',      [CertificationController::class, 'store']);
    Route::get('/certifications/{certification}/document/{type}', [CertificationController::class, 'document'])->whereNumber('certification');

    // Certification de compte (admin)
    Route::middleware('admin')->group(function () {
        Route::get('/certifications',                       [CertificationController::class, 'index']);
        Route::post('/certifications/{certification}/approuver', [CertificationController::class, 'approuver'])->whereNumber('certification');
        Route::post('/certifications/{certification}/rejeter',   [CertificationController::class, 'rejeter'])->whereNumber('certification');

        // Localisation (ville/arrondissement/quartier) — création/modification/
        // suppression réservées à l'admin ; la lecture (`index`) reste publique
        // (cf. plus haut, utilisée par les selects de publication d'annonce).
        Route::post('/villes',                    [VilleController::class, 'store']);
        Route::put('/villes/{ville}',              [VilleController::class, 'update'])->whereNumber('ville');
        Route::delete('/villes/{ville}',           [VilleController::class, 'destroy'])->whereNumber('ville');
        Route::post('/arrondissements',            [ArrondissementController::class, 'store']);
        Route::put('/arrondissements/{arrondissement}', [ArrondissementController::class, 'update'])->whereNumber('arrondissement');
        Route::delete('/arrondissements/{arrondissement}', [ArrondissementController::class, 'destroy'])->whereNumber('arrondissement');
        Route::post('/quartiers',                  [QuartierController::class, 'store']);
        Route::put('/quartiers/{quartier}',        [QuartierController::class, 'update'])->whereNumber('quartier');
        Route::delete('/quartiers/{quartier}',     [QuartierController::class, 'destroy'])->whereNumber('quartier');

        // Gestion des comptes (statut, sessions, mot de passe) — distinct de
        // GET /users (recherche pour assignation de bien, ouvert à tout
        // utilisateur authentifié) : ici, actions sensibles réservées admin.
        Route::get('/comptes',                              [CompteAdminController::class, 'index']);
        Route::put('/comptes/{user}/statut',                 [CompteAdminController::class, 'changerStatut'])->whereNumber('user');
        Route::post('/comptes/{user}/revoquer-sessions',     [CompteAdminController::class, 'revoquerSessions'])->whereNumber('user');
        Route::post('/comptes/{user}/reinitialiser-mot-de-passe', [CompteAdminController::class, 'reinitialiserMotDePasse'])->whereNumber('user');
    });

    // Statistiques Membre
    Route::get('/member/stats',                    [MemberStatsController::class, 'index']);
    Route::get('/member/biens-loues',             [MemberStatsController::class, 'biensLoues']);
    Route::get('/member/biens-vendus',             [MemberStatsController::class, 'biensVendus']);
    Route::get('/member/biens-vente',              [MemberStatsController::class, 'biensEnVente']);
    Route::get('/member/biens-location',           [MemberStatsController::class, 'biensEnLocation']);
    Route::get('/member/revenus-mensuels',         [MemberStatsController::class, 'revenusMensuels']);
    Route::get('/member/historique-transactions',  [MemberStatsController::class, 'historiqueTransactions']);
});
