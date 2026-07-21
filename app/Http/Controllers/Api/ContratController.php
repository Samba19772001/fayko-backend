<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\User;
use Illuminate\Http\Request;

// Cahier des charges §3.3 - Création et consultation des contrats.
// Convention retenue : celui qui crée le contrat est toujours le prêteur ;
// l'autre partie (l'emprunteur) est identifiée par son numéro de téléphone.
class ContratController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'preteur'); // preteur | emprunteur

        $contrats = $role === 'emprunteur'
            ? $request->user()->contratsEnTantQuEmprunteur()
            : $request->user()->contratsEnTantQuePreteur();

        return response()->json(
            $contrats->with(['preteur', 'emprunteur', 'signatures'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'telephone_autre_partie' => ['required', 'string'],
            'montant' => ['required', 'numeric', 'min:1'],
            'date_remise_fonds' => ['required', 'date'],
            'date_echeance' => ['required', 'date', 'after:date_remise_fonds'],
            'taux_interet' => ['nullable', 'numeric', 'min:0'],
            'garanties' => ['nullable', 'string'],
            'mode_remboursement' => ['required', 'in:especes,mobile_money,virement'],
        ]);

        // Rappel : la route sera protégée par le middleware 'verifie', donc en
        // théorie $request->user() est déjà vérifié. On revérifie quand même
        // ici par sécurité (défense en profondeur).
        if (! $request->user()->estVerifie()) {
            return response()->json(['message' => 'Votre compte doit être vérifié avant de créer un contrat.'], 403);
        }

        $autrePartie = User::where('telephone', $data['telephone_autre_partie'])->first();

        if (! $autrePartie) {
            return response()->json(['message' => 'Aucun utilisateur Fayko trouvé avec ce numéro.'], 404);
        }

        if ($autrePartie->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas créer un contrat avec vous-même.'], 422);
        }

        if (! $autrePartie->estVerifie()) {
            return response()->json(['message' => "L'autre partie doit également avoir un compte vérifié."], 422);
        }

        $contrat = Contrat::create([
            'preteur_id' => $request->user()->id,
            'emprunteur_id' => $autrePartie->id,
            'montant' => $data['montant'],
            'date_remise_fonds' => $data['date_remise_fonds'],
            'date_echeance' => $data['date_echeance'],
            'taux_interet' => $data['taux_interet'] ?? null,
            'garanties' => $data['garanties'] ?? null,
            'mode_remboursement' => $data['mode_remboursement'],
        ]);

        // TODO : notifier l'autre partie (SMS/push) qu'un contrat attend sa signature.

        return response()->json($contrat->load(['preteur', 'emprunteur']), 201);
    }

    public function show(Request $request, Contrat $contrat)
    {
        $this->autoriserAcces($request, $contrat);

        return response()->json(
            $contrat->load(['preteur', 'emprunteur', 'signatures.signataire', 'paiements'])
        );
    }

    private function autoriserAcces(Request $request, Contrat $contrat): void
    {
        abort_unless(
            in_array($request->user()->id, [$contrat->preteur_id, $contrat->emprunteur_id]),
            403,
            "Vous n'êtes pas partie à ce contrat."
        );
    }
}