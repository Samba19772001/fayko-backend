<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\Paiement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paiement des 500 FCFA de frais de contrat (§3.4) via Wave / Orange Money /
 * Free Money, en passant par un agrégateur (ex. PayTech, InTouch).
 *
 * Comme pour OtpService, le vrai appel réseau est abstrait derrière
 * MOBILE_MONEY_AGGREGATOR. En mode 'simulation' (par défaut en dev), le
 * paiement est considéré réussi immédiatement, ce qui permet de tester tout
 * le parcours de signature sans dépendre d'un vrai compte marchand.
 */
class MobileMoneyService
{
    public function initierPaiement(Paiement $paiement): array
    {
        $aggregateur = config('services.mobile_money.aggregator', 'simulation');
        $reference = 'FAYKO-'.Str::upper(Str::random(10));

        $paiement->update(['reference_transaction' => $reference]);

        if ($aggregateur === 'simulation') {
            Log::info("[Mobile money SIMULATION] Paiement {$reference} de {$paiement->montant} FCFA via {$paiement->operateur} -> succès automatique");
            $this->traiterCallback(['reference' => $reference, 'status' => 'SUCCESS']);

            return ['reference' => $reference, 'redirect_url' => null, 'mode' => 'simulation'];
        }

        // TODO brancher le vrai agrégateur ici (PayTech, InTouch...) en lisant
        // MOBILE_MONEY_API_KEY / MOBILE_MONEY_API_SECRET, et renvoyer l'URL ou
        // le code USSD à afficher à l'utilisateur pour valider le paiement.
        throw new \RuntimeException("Agrégateur mobile money '{$aggregateur}' non implémenté.");
    }

    /**
     * Appelé soit par le webhook de l'agrégateur (production), soit
     * directement en mode simulation (développement).
     */
    public function traiterCallback(array $payload): void
    {
        $paiement = Paiement::where('reference_transaction', $payload['reference'] ?? null)->first();

        if (! $paiement) {
            Log::warning('Callback mobile money reçu pour une référence inconnue', $payload);
            return;
        }

        $reussi = ($payload['status'] ?? null) === 'SUCCESS';
        $paiement->update(['statut' => $reussi ? 'reussi' : 'echoue']);

        if ($reussi) {
            $this->finaliserContrat($paiement->contrat);
        }
    }

    private function finaliserContrat(Contrat $contrat): void
    {
        $contrat->update(['frais_payes' => true]);

        if ($contrat->estConclu()) {
            $contrat->update([
                'statut' => 'actif',
                'conclu_at' => now(),
            ]);
            app(\App\Services\ContractPdfService::class)->genererPdf($contrat);
        }
    }
}
