<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Cahier des charges §3.1, révisé : l'inscription vérifie le numéro par
// OTP une seule fois (pour prouver que l'utilisateur possède bien ce
// téléphone), puis toutes les connexions suivantes se font par
// téléphone + mot de passe. Aucun OTP n'est redemandé pour se connecter.
class AuthController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    /**
     * Étape 1 de l'inscription : envoie un code par SMS pour vérifier
     * le numéro. Refuse si ce numéro est déjà enregistré (mot de passe
     * déjà défini) — dans ce cas, l'utilisateur doit se connecter.
     */
    public function inscriptionDemanderCode(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
        ]);

        $user = User::firstOrCreate(['telephone' => $data['telephone']]);

        if (filled($user->mot_de_passe)) {
            return response()->json([
                'message' => 'Ce numéro est déjà enregistré. Veuillez vous connecter.',
            ], 422);
        }

        $this->otpService->genererEtEnvoyer($user, 'inscription');

        return response()->json([
            'message' => 'Un code a été envoyé par SMS.',
        ]);
    }

    /**
     * Étape 2 de l'inscription : valide le code (preuve de possession du
     * numéro) et définit le mot de passe en une seule opération. Connecte
     * immédiatement l'utilisateur après coup.
     */
    public function inscriptionDefinirMotDePasse(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
            'mot_de_passe' => ['required', 'string', 'min:6'],
        ]);

        $user = User::where('telephone', $data['telephone'])->firstOrFail();

        if (filled($user->mot_de_passe)) {
            return response()->json([
                'message' => 'Ce numéro est déjà enregistré. Veuillez vous connecter.',
            ], 422);
        }

        if (! $this->otpService->verifier($user, $data['code'], 'inscription')) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $user->update([
            'mot_de_passe' => Hash::make($data['mot_de_passe']),
            'telephone_verifie_at' => now(),
        ]);

        $token = $user->createToken('fayko-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'profil_complet' => filled($user->nom) && filled($user->prenom),
        ], 201);
    }

    /**
     * Connexion standard : téléphone + mot de passe. Aucun OTP.
     * Le message d'erreur reste volontairement générique (numéro
     * inconnu ou mot de passe incorrect) pour ne pas révéler si un
     * numéro est enregistré ou non.
     */
    public function connexion(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'mot_de_passe' => ['required', 'string'],
        ]);

        $user = User::where('telephone', $data['telephone'])->first();

        if (! $user || blank($user->mot_de_passe) || ! Hash::check($data['mot_de_passe'], $user->mot_de_passe)) {
            return response()->json([
                'message' => 'Numéro ou mot de passe incorrect.',
            ], 422);
        }

        $token = $user->createToken('fayko-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'profil_complet' => filled($user->nom) && filled($user->prenom),
        ]);
    }

    /**
     * Complète le profil (nom, prénom, CNI). Ne suffit pas à passer le compte
     * en "verifie" — ça, c'est la vérification d'identité (§3.2), traitée
     * séparément (upload photo CNI + contrôle, pas couvert ici).
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