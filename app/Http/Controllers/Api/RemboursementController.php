<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Remboursement;
use Illuminate\Http\Request;

// Cahier des charges §3.6 - Déclaration et confirmation des remboursements.
class RemboursementController extends Controller
{
    public function declarer(Request $request, Contrat $contrat)
    {
        $this->verifierPartiePrenante($request, $contrat);

        if (is_null($contrat->statut)) {
            return response()->json(['message' => "Ce contrat n'est pas encore conclu."], 422);
        }

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'reference_transaction' => ['required', 'string', 'max:100'],
        ]);

        $remboursement = Remboursement::create([
            'contrat_id' => $contrat->id,
            'montant' => $data['montant'],
            'reference_transaction' => $data['reference_transaction'],
            'declare_par' => $request->user()->id,
            'statut_confirmation' => 'en_attente',
            'date_declaration' => now(),
        ]);

        // TODO : notifier l'autre partie qu'un remboursement attend confirmation.

        return response()->json($remboursement, 201);
    }

    public function confirmer(Request $request, Remboursement $remboursement)
    {
        $this->verifierAutrePartie($request, $remboursement);

        $remboursement->update(['statut_confirmation' => 'confirme']);

        $contrat = $remboursement->contrat;

        if ($contrat->estEntierementRembourse()) {
            $contrat->update(['statut' => 'solde']);
        }

        return response()->json([
            'remboursement' => $remboursement->fresh(),
            'contrat' => $contrat->fresh(),
        ]);
    }

    public function contester(Request $request, Remboursement $remboursement)
    {
        $this->verifierAutrePartie($request, $remboursement);

        $remboursement->update(['statut_confirmation' => 'conteste']);
        $remboursement->contrat->update(['statut' => 'litige']);

        return response()->json([
            'remboursement' => $remboursement->fresh(),
            'contrat' => $remboursement->contrat->fresh(),
        ]);
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
     * Seule la partie qui n'a PAS déclaré le remboursement peut le confirmer
     * ou le contester — on ne peut pas être juge et partie de sa propre
     * déclaration.
     */
    private function verifierAutrePartie(Request $request, Remboursement $remboursement): void
    {
        $contrat = $remboursement->contrat;

        abort_unless(
            in_array($request->user()->id, [$contrat->preteur_id, $contrat->emprunteur_id]),
            403,
            "Vous n'êtes pas partie à ce contrat."
        );

        abort_if(
            $request->user()->id === $remboursement->declare_par,
            403,
            'Vous ne pouvez pas confirmer ou contester votre propre déclaration.'
        );
    }

        /**
     * Permet à celui qui a contesté un remboursement de retirer sa
     * contestation — par exemple si le désaccord s'est réglé en dehors de
     * l'app. Fayko ne juge jamais qui avait raison : seule la personne à
     * l'origine de la contestation peut la retirer, et le remboursement
     * revient simplement en attente de confirmation normale.
     */
    public function retirerContestation(Request $request, Remboursement $remboursement)
    {
        $contrat = $remboursement->contrat;

        abort_unless(
            in_array($request->user()->id, [$contrat->preteur_id, $contrat->emprunteur_id]),
            403,
            "Vous n'êtes pas partie à ce contrat."
        );

        if ($remboursement->statut_confirmation !== 'conteste') {
            return response()->json(['message' => "Ce remboursement n'est pas contesté."], 422);
        }

        // Seul l'auteur de la contestation peut la retirer. On le retrouve
        // en cherchant qui N'EST PAS le déclarant parmi les deux parties.
        $contestataireId = $contrat->preteur_id === $remboursement->declare_par
            ? $contrat->emprunteur_id
            : $contrat->preteur_id;

        abort_unless($request->user()->id === $contestataireId, 403, "Seule la personne ayant contesté peut retirer sa contestation.");

        $remboursement->update(['statut_confirmation' => 'en_attente']);
        $contrat->recalculerStatut();

        return response()->json([
            'remboursement' => $remboursement->fresh(),
            'contrat' => $contrat->fresh(),
        ]);
    }
}