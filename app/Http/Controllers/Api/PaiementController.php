<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Paiement;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;

// Cahier des charges §3.4 - Paiement des frais de 500 FCFA une fois le
// contrat doublement signé.
class PaiementController extends Controller
{
    public function __construct(private MobileMoneyService $mobileMoneyService)
    {
    }

    public function initier(Request $request, Contrat $contrat)
    {
        abort_unless(
            in_array($request->user()->id, [$contrat->preteur_id, $contrat->emprunteur_id]),
            403,
            "Vous n'êtes pas partie à ce contrat."
        );

        if ($contrat->signatures()->count() < 2) {
            return response()->json(['message' => 'Les deux parties doivent signer avant de payer les frais.'], 422);
        }

        if ($contrat->frais_payes) {
            return response()->json(['message' => 'Les frais ont déjà été payés.'], 422);
        }

        $data = $request->validate([
            'operateur' => ['required', 'in:wave,orange_money,free_money'],
        ]);

        $paiement = Paiement::create([
            'contrat_id' => $contrat->id,
            'montant' => config('fayko.frais_contrat'),
            'operateur' => $data['operateur'],
            'statut' => 'en_attente',
        ]);

        $resultat = $this->mobileMoneyService->initierPaiement($paiement);

        return response()->json([
            'paiement' => $paiement->fresh(),
            'contrat' => $contrat->fresh(),
            ...$resultat,
        ]);
    }

    /**
     * Webhook appelé par l'agrégateur mobile money en production. En mode
     * simulation (dev), ce endpoint n'est jamais réellement sollicité —
     * initierPaiement() déclenche directement le callback en interne.
     */
    public function webhook(Request $request)
    {
        // TODO : vérifier la signature du webhook via MOBILE_MONEY_CALLBACK_SECRET
        // avant de faire confiance au contenu (§4.1) — indispensable en production.
        $this->mobileMoneyService->traiterCallback($request->all());

        return response()->json(['message' => 'ok']);
    }
}