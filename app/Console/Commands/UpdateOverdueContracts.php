<?php

namespace App\Console\Commands;

use App\Models\Contrat;
use Illuminate\Console\Command;

// Cahier des charges §3.6 - Fait automatiquement évoluer le statut des
// contrats selon l'échéance :
//   actif      -> en_retard   (dès que l'échéance est dépassée)
//   en_retard  -> impaye      (après un délai de grâce, FAYKO_RETARD_IMPAYE_JOURS)
class UpdateOverdueContracts extends Command
{
    protected $signature = 'fayko:update-overdue-contracts';
    protected $description = "Fait passer les contrats en retard puis en impayé selon l'échéance dépassée.";

    public function handle(): int
    {
        $joursDeGrace = config('fayko.retard_impaye_jours');

        $nbEnRetard = Contrat::enRetardNonSignale()->update(['statut' => 'en_retard']);
        $this->info("{$nbEnRetard} contrat(s) passé(s) en 'en_retard'.");

        $nbImpayes = Contrat::eligiblesImpaye($joursDeGrace)->update(['statut' => 'impaye']);
        $this->info("{$nbImpayes} contrat(s) passé(s) en 'impaye'.");

        // TODO : notifier les parties concernées (SMS/push) à chaque changement de statut.

        return self::SUCCESS;
    }
}