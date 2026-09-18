<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Remboursement;
use Illuminate\Http\Request;

// Arbitrage manuel des litiges (§3.6). Fayko ne détermine jamais
// algorithmiquement qui dit vrai : un agent tranche, en s'appuyant sur la
// référence de transaction fournie, et sa décision est toujours motivée.
class LitigeController extends Controller
{
    public function index()
    {
        $contratsEnLitige = \App\Models\Contrat::where('statut', 'litige')
            ->with(['preteur', 'emprunteur', 'remboursements' => function ($q) {
                $q->where('statut_confirmation', 'conteste')->with('declarant');
            }])
            ->get();

        return view('admin.litiges', compact('contratsEnLitige'));
    }

    public function valider(Request $request, Remboursement $remboursement)
    {
        $data = $request->validate([
            'motif_admin' => ['required', 'string', 'max:500'],
        ]);

        $remboursement->update([
            'statut_confirmation' => 'confirme',
            'motif_admin' => $data['motif_admin'],
        ]);

        $remboursement->contrat->recalculerStatut();

        return back()->with('succes', 'Remboursement validé, contrat mis à jour.');
    }

    public function rejeter(Request $request, Remboursement $remboursement)
    {
        $data = $request->validate([
            'motif_admin' => ['required', 'string', 'max:500'],
        ]);

        // Le remboursement reste "conteste" (il n'est jamais comptabilisé
        // dans le total remboursé), mais on documente la décision et on
        // sort le contrat de l'état bloqué "litige".
        $remboursement->update(['motif_admin' => $data['motif_admin']]);
        $remboursement->contrat->recalculerStatut();

        return back()->with('succes', 'Déclaration rejetée, contrat mis à jour.');
    }
}