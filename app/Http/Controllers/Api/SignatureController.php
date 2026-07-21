<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Signature;
use App\Services\OtpService;
use Illuminate\Http\Request;

// Cahier des charges §3.5 - Signature électronique à double confirmation OTP.
class SignatureController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    /**
     * Étape 1 : demande le code de confirmation par SMS.
     */
    public function demanderCode(Request $request, Contrat $contrat)
    {
        $this->verifierPartiePrenante($request, $contrat);

        // Le contexte inclut l'id du contrat : sans ça, un utilisateur qui
        // signerait deux contrats à la suite verrait le code du premier
        // écrasé par celui du second (même clé de cache).
        $this->otpService->genererEtEnvoyer($request->user(), "signature-{$contrat->id}");

        return response()->json(['message' => 'Code de confirmation envoyé par SMS.']);
    }

    /**
     * Étape 2 : valide le code et enregistre la signature.
     */
    public function signer(Request $request, Contrat $contrat)
    {
        $this->verifierPartiePrenante($request, $contrat);

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'device_fingerprint' => ['required', 'string'], // fourni par l'app mobile
        ]);

        if (! $request->user()->estVerifie()) {
            return response()->json(['message' => 'Compte non vérifié.'], 403);
        }

        if ($contrat->signatures()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Vous avez déjà signé ce contrat.'], 422);
        }

        if (! $this->otpService->verifier($request->user(), $data['code'], "signature-{$contrat->id}")) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $signature = Signature::create([
            'contrat_id' => $contrat->id,
            'user_id' => $request->user()->id,
            'signed_at' => now(),
            'ip_address' => $request->ip(),
            'device_fingerprint' => $data['device_fingerprint'],
            'otp_valide' => true,
            'hash_document' => $this->calculerHashContrat($contrat),
        ]);

        return response()->json([
            'signature' => $signature,
            'contrat_pret_pour_paiement' => $contrat->signatures()->count() === 2,
        ], 201);
    }

    private function verifierPartiePrenante(Request $request, Contrat $contrat): void
    {
        abort_unless(
            in_array($request->user()->id, [$contrat->preteur_id, $contrat->emprunteur_id]),
            403,
            "Vous n'êtes pas partie à ce contrat."
        );
    }

    /**
     * Empreinte des termes du contrat au moment T. Le PDF final n'existe pas
     * encore à ce stade (il ne sera généré qu'après paiement des frais) ; ce
     * hash sert à prouver que les termes n'ont pas changé entre les deux
     * signatures.
     */
    private function calculerHashContrat(Contrat $contrat): string
    {
        return hash('sha256', json_encode([
            $contrat->id, $contrat->preteur_id, $contrat->emprunteur_id,
            (string) $contrat->montant, $contrat->date_remise_fonds->toDateString(),
            $contrat->date_echeance->toDateString(), (string) $contrat->taux_interet,
            $contrat->garanties, $contrat->mode_remboursement,
        ]));
    }
}