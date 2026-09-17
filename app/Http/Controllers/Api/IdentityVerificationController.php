<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Cahier des charges §3.2 - Upload des photos de CNI (recto/verso) et
// d'un selfie optionnel. Fait passer le compte en "en_attente" jusqu'à
// validation par un agent via le panneau admin.
//
// On stocke des CHEMINS RELATIFS (pas des URLs complètes) : l'URL finale
// est reconstruite à l'affichage via asset()/Storage::url(). Ça évite que
// les liens enregistrés se cassent si APP_URL change (ex. déploiement).
class IdentityVerificationController extends Controller
{
    public function upload(Request $request)
    {
        $user = $request->user();

        if ($user->estVerifie()) {
            return response()->json(['message' => 'Votre compte est déjà vérifié.'], 422);
        }

        $data = $request->validate([
            'cni_recto' => ['required', 'image', 'max:5120'],
            'cni_verso' => ['required', 'image', 'max:5120'],
            'selfie' => ['nullable', 'image', 'max:5120'],
        ]);

        $cheminRecto = $request->file('cni_recto')->store('identite/' . $user->id, 'public');
        $cheminVerso = $request->file('cni_verso')->store('identite/' . $user->id, 'public');
        $cheminSelfie = $request->hasFile('selfie')
            ? $request->file('selfie')->store('identite/' . $user->id, 'public')
            : null;

        $user->update([
            'cni_recto_url' => $cheminRecto,
            'cni_verso_url' => $cheminVerso,
            'selfie_url' => $cheminSelfie ?? $user->selfie_url,
            'statut_verification' => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Documents envoyés. Votre compte est en attente de validation.',
            'user' => $user->fresh(),
        ]);
    }
}