<?php

namespace App\Services;

use App\Models\Contrat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Génère le PDF final d'un contrat conclu et calcule son empreinte SHA-256
 * (§3.5 / §5.3 : intégrité du document). Appelé automatiquement une fois
 * que les deux signatures sont posées et les frais payés (MobileMoneyService).
 */
class ContractPdfService
{
    public function genererPdf(Contrat $contrat): void
    {
        $contrat->loadMissing(['preteur', 'emprunteur', 'signatures.signataire']);

        $pdf = Pdf::loadView('pdf.contrat', ['contrat' => $contrat]);
        $contenuBinaire = $pdf->output();

        $hash = hash('sha256', $contenuBinaire);
        $nomFichier = "contrats/Contrat_Fayko_{$contrat->id}.pdf";

        Storage::disk('public')->put($nomFichier, $contenuBinaire);

        $contrat->update([
            'pdf_url' => Storage::disk('public')->url($nomFichier),
            'hash_document' => $hash,
        ]);
    }
}
