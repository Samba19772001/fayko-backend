<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #16233A; }
    h1 { font-size: 18px; color: #1E3A5F; margin-bottom: 2px; }
    .sous-titre { color: #5B6B7F; font-size: 11px; margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    td { padding: 6px 4px; border-bottom: 1px solid #D8DEE2; }
    td.label { color: #5B6B7F; width: 40%; }
    td.valeur { font-weight: bold; }
    .signatures { margin-top: 24px; }
    .signature-bloc { border: 1px solid #D8DEE2; padding: 10px; margin-bottom: 10px; }
    .pied { margin-top: 30px; font-size: 9px; color: #5B6B7F; border-top: 1px solid #D8DEE2; padding-top: 8px; }
</style>
</head>
<body>
    <h1>Contrat de prêt n°{{ $contrat->id }}</h1>
    <div class="sous-titre">Généré via Fayko — contrat numérique entre particuliers</div>

    <table>
        <tr><td class="label">Prêteur</td><td class="valeur">{{ $contrat->preteur->prenom }} {{ $contrat->preteur->nom }} ({{ $contrat->preteur->telephone }})</td></tr>
        <tr><td class="label">Emprunteur</td><td class="valeur">{{ $contrat->emprunteur->prenom }} {{ $contrat->emprunteur->nom }} ({{ $contrat->emprunteur->telephone }})</td></tr>
        <tr><td class="label">Montant</td><td class="valeur">{{ number_format($contrat->montant, 0, ',', ' ') }} {{ $contrat->devise }}</td></tr>
        <tr><td class="label">Date de remise des fonds</td><td class="valeur">{{ $contrat->date_remise_fonds->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Date d'échéance</td><td class="valeur">{{ $contrat->date_echeance->format('d/m/Y') }}</td></tr>
        @if($contrat->taux_interet)
        <tr><td class="label">Taux d'intérêt</td><td class="valeur">{{ $contrat->taux_interet }} %</td></tr>
        @endif
        @if($contrat->garanties)
        <tr><td class="label">Garanties</td><td class="valeur">{{ $contrat->garanties }}</td></tr>
        @endif
        <tr><td class="label">Mode de remboursement</td><td class="valeur">{{ ucfirst(str_replace('_', ' ', $contrat->mode_remboursement)) }}</td></tr>
    </table>

    <div class="signatures">
        <strong>Signatures électroniques</strong>
        @foreach($contrat->signatures as $signature)
        <div class="signature-bloc">
            {{ $signature->signataire->prenom }} {{ $signature->signataire->nom }} —
            signé le {{ $signature->signed_at->format('d/m/Y à H:i') }}
            (OTP {{ $signature->otp_valide ? 'validé' : 'non validé' }})
        </div>
        @endforeach
    </div>

    <div class="pied">
        Ce document a été conclu électroniquement via Fayko le {{ $contrat->conclu_at?->format('d/m/Y à H:i') }}.
        Son intégrité peut être vérifiée via son empreinte numérique, disponible sur simple demande auprès des parties.
    </div>
</body>
</html>
