<?php

use App\Http\Controllers\Api\AccessCodeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContratController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\RemboursementController;
use App\Http\Controllers\Api\SignatureController;
use Illuminate\Support\Facades\Route;

// --- Authentification (§3.1) — public ---
// Inscription en deux étapes (OTP pour prouver le numéro, puis mot de passe).
Route::post('/auth/inscription/demander-code', [AuthController::class, 'inscriptionDemanderCode']);
Route::post('/auth/inscription/definir-mot-de-passe', [AuthController::class, 'inscriptionDefinirMotDePasse']);
// Connexion standard, sans OTP.
Route::post('/auth/connexion', [AuthController::class, 'connexion']);

// --- Webhook mobile money (§3.4) — public, sécurisé par signature interne ---
Route::post('/paiements/webhook', [PaiementController::class, 'webhook']);

// --- Routes protégées (jeton Sanctum requis) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/completer-profil', [AuthController::class, 'completerProfil']);
    Route::post('/auth/verification-identite', [\App\Http\Controllers\Api\IdentityVerificationController::class, 'upload']);
    Route::get('/auth/moi', [AuthController::class, 'moi']);
    Route::post('/auth/deconnexion', [AuthController::class, 'deconnexion']);

    // Contrats (§3.3)
    Route::get('/contrats', [ContratController::class, 'index']);
    Route::get('/contrats/{contrat}', [ContratController::class, 'show']);
    Route::post('/contrats', [ContratController::class, 'store'])->middleware('verifie');

    // Signature électronique (§3.5)
    Route::post('/contrats/{contrat}/signature/demander-code', [SignatureController::class, 'demanderCode'])->middleware('verifie');
    Route::post('/contrats/{contrat}/signature', [SignatureController::class, 'signer'])->middleware('verifie');

    // Paiement des frais (§3.4)
    Route::post('/contrats/{contrat}/paiement', [PaiementController::class, 'initier']);

    // Remboursements (§3.6)
    Route::post('/contrats/{contrat}/remboursements', [RemboursementController::class, 'declarer']);
    Route::post('/remboursements/{remboursement}/confirmer', [RemboursementController::class, 'confirmer']);
    Route::post('/remboursements/{remboursement}/contester', [RemboursementController::class, 'contester']);

    // Solvabilité (§3.7)
    Route::post('/solvabilite/generer-code', [AccessCodeController::class, 'generer']);
    Route::post('/solvabilite/verifier-code', [AccessCodeController::class, 'verifier']);
    Route::get('/solvabilite/historique', [AccessCodeController::class, 'historique']);
});