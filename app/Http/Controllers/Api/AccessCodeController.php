<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\User;
use Illuminate\Http\Request;

// Cahier des charges §3.7 - Vérification de solvabilité par code à usage unique.
class AccessCodeController extends Controller
{
    /**
     * Génère un code à 6 chiffres pour le débiteur connecté, à communiquer
     * de vive voix à un prêteur potentiel.
     */
    public function generer(Request $request)
    {
        // On invalide les codes actifs précédents : un seul code valide à la fois.
        AccessCode::where('user_id', $request->user()->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->delete();

        $code = (string) random_int(100000, 999999);

        $accessCode = AccessCode::create([
            'user_id' => $request->user()->id,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(config('fayko.delai_code_minutes')),
        ]);

        return response()->json([
            'code' => $code, // affiché une seule fois : jamais restocké en clair
            'expire_a' => $accessCode->expires_at,
        ]);
    }

    /**
     * Un prêteur saisit le code reçu pour consulter la solvabilité du débiteur.
     */
    public function verifier(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $accessCode = AccessCode::where('code_hash', hash('sha256', $data['code']))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $accessCode) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $accessCode->update([
            'used_at' => now(),
            'used_by_user_id' => $request->user()->id,
        ]);

        return response()->json($this->resumeSolvabilite($accessCode->debiteur));
    }

    /**
     * "Qui a consulté mon profil" (§3.7) — journal des consultations reçues.
     */
    public function historique(Request $request)
    {
        $consultations = AccessCode::where('user_id', $request->user()->id)
            ->whereNotNull('used_at')
            ->with('consultePar:id,nom,prenom')
            ->latest('used_at')
            ->get()
            ->map(fn ($ac) => [
                'consulte_par' => trim(($ac->consultePar?->prenom ?? '').' '.($ac->consultePar?->nom ?? '')) ?: 'Utilisateur Fayko',
                'date' => $ac->used_at,
            ]);

        return response()->json($consultations);
    }

    /**
     * Ne renvoie QUE montants, échéances et statuts — jamais l'identité des
     * prêteurs concernés. C'est la règle de confidentialité centrale du §3.7.
     */
    private function resumeSolvabilite(User $debiteur): array
    {
        $contrats = $debiteur->contratsEnTantQuEmprunteur()->get();

        $formatter = fn ($c) => [
            'montant' => $c->montant,
            'date_echeance' => $c->date_echeance->toDateString(),
            'statut' => $c->statut,
        ];

        return [
            'actifs' => $contrats->where('statut', 'actif')->map($formatter)->values(),
            'en_retard' => $contrats->where('statut', 'en_retard')->map($formatter)->values(),
            'impayes' => $contrats->where('statut', 'impaye')->map($formatter)->values(),
            'soldes' => $contrats->where('statut', 'solde')->map($formatter)->values(),
            'litiges' => $contrats->where('statut', 'litige')->map($formatter)->values(),
        ];
    }
}