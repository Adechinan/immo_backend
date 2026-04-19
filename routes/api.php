<?php

use App\Http\Controllers\AchatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MemberStatsController;
use App\Http\Controllers\MensualiteController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\TofController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Immobilier Laravel 12
|--------------------------------------------------------------------------
*/

// --- Auth (public) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// --- Public ---
Route::get('/biens',            [BienController::class, 'index']);
Route::get('/biens/dernieres-annonces', [BienController::class, 'dernieresAnnonces']);
Route::get('/biens/{bien}',     [BienController::class, 'show']);
Route::get('/biens/{bien}/tofs',[TofController::class, 'index']);
Route::get('/options',          [OptionController::class, 'index']);

// Demandes (envoi sans authentification)
Route::post('/biens/{bien}/demandes', [DemandeController::class, 'store']);

// --- Authentifié ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Biens (CRUD — admin/propriétaire)
    Route::post('/biens',              [BienController::class, 'store']);
    Route::put('/biens/{bien}',        [BienController::class, 'update']);
    Route::patch('/biens/{bien}',      [BienController::class, 'update']);
    Route::delete('/biens/{bien}',     [BienController::class, 'destroy']);

    // Tofs
    Route::post('/biens/{bien}/tofs',          [TofController::class, 'store']);
    Route::delete('/biens/{bien}/tofs/{tof}',  [TofController::class, 'destroy']);

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
    Route::delete('/locations/{location}',[LocationController::class, 'destroy']);

    // Mensualités (imbriquées dans Location)
    Route::get('/locations/{location}/mensualites',                    [MensualiteController::class, 'index']);
    Route::post('/locations/{location}/mensualites',                   [MensualiteController::class, 'store']);
    Route::get('/locations/{location}/mensualites/{mensualite}',       [MensualiteController::class, 'show']);
    Route::put('/locations/{location}/mensualites/{mensualite}',       [MensualiteController::class, 'update']);
    Route::delete('/locations/{location}/mensualites/{mensualite}',    [MensualiteController::class, 'destroy']);

    // Demandes (consultation admin)
    Route::get('/demandes',           [DemandeController::class, 'index']);
    Route::get('/demandes/{demande}', [DemandeController::class, 'show']);
    Route::delete('/demandes/{demande}', [DemandeController::class, 'destroy']);

    // Statistiques Membre
    Route::get('/member/stats',                    [MemberStatsController::class, 'index']);
    Route::get('/member/biens-loues',             [MemberStatsController::class, 'biensLoues']);
    Route::get('/member/biens-vente',              [MemberStatsController::class, 'biensEnVente']);
    Route::get('/member/biens-location',           [MemberStatsController::class, 'biensEnLocation']);
    Route::get('/member/revenus-mensuels',         [MemberStatsController::class, 'revenusMensuels']);
    Route::get('/member/historique-transactions',  [MemberStatsController::class, 'historiqueTransactions']);
});
