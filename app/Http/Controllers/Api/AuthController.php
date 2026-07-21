<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;

// Cahier des charges §3.1 - Inscription / connexion par téléphone + OTP.
// Le flux est unifié : demanderCode() crée le compte s'il n'existe pas encore.
class AuthController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    /**
     * Étape 1 : demande d'un code envoyé par SMS.
     */
    public function demanderCode(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
        ]);

        $user = User::firstOrCreate(['telephone' => $data['telephone']]);

        $this->otpService->genererEtEnvoyer($user, 'connexion');

        return response()->json([
            'message' => 'Un code a été envoyé par SMS.',
        ]);
    }

    /**
     * Étape 2 : vérification du code, émission d'un jeton d'API.
     */
    public function verifierCode(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('telephone', $data['telephone'])->firstOrFail();

        if (! $this->otpService->verifier($user, $data['code'], 'connexion')) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $user->update(['telephone_verifie_at' => now()]);
        $token = $user->createToken('fayko-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'profil_complet' => filled($user->nom) && filled($user->prenom),
        ]);
    }

    /**
     * Complète le profil (nom, prénom, CNI). Ne suffit pas à passer le compte
     * en "verifie" — ça, c'est la vérification d'identité (§3.2), qu'on
     * traitera séparément (upload photo CNI + contrôle, pas couvert ici).
     */
    public function completerProfil(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'numero_cni' => ['required', 'string', 'max:50'],
            'date_naissance' => ['nullable', 'date'],
            'ville' => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->update($data);

        return response()->json(['user' => $request->user()->fresh()]);
    }

    public function moi(Request $request)
    {
        return response()->json($request->user());
    }

    public function deconnexion(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}