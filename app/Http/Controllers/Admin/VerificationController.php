<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $enAttente = User::where('statut_verification', 'en_attente')
            ->orderBy('updated_at')
            ->get();

        return view('admin.dashboard', compact('enAttente'));
    }

    public function valider(User $user)
    {
        if (! $user->estEnAttenteDeVerification()) {
            return back()->with('erreur', "Ce compte n'est pas en attente de validation.");
        }

        $user->update(['statut_verification' => 'verifie']);

        // TODO : notifier l'utilisateur (SMS/push) que son compte est vérifié.

        return back()->with('succes', "Compte de {$user->telephone} vérifié.");
    }

    public function rejeter(Request $request, User $user)
    {
        if (! $user->estEnAttenteDeVerification()) {
            return back()->with('erreur', "Ce compte n'est pas en attente de validation.");
        }

        // On repasse le compte à "non_verifie" pour qu'il puisse soumettre
        // de nouvelles photos ; les anciennes restent en base à titre de
        // trace, mais ne sont plus considérées valides.
        $user->update(['statut_verification' => 'non_verifie']);

        // TODO : notifier l'utilisateur du rejet avec le motif ($request->motif).

        return back()->with('succes', "Compte de {$user->telephone} rejeté.");
    }
}