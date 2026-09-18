<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fayko — Litiges</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <h1 class="text-lg font-semibold text-slate-800">Fayko — Litiges</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800">Vérifications d'identité</a>
            <a href="{{ route('admin.statistiques') }}" class="text-sm text-slate-500 hover:text-slate-800">Statistiques</a>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">Se déconnecter</button>
        </form>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-8">
        @if (session('succes'))
            <div class="bg-emerald-50 text-emerald-700 text-sm rounded-lg p-3 mb-6">{{ session('succes') }}</div>
        @endif

        <div class="bg-plum-50 bg-purple-50 text-purple-800 text-sm rounded-lg p-4 mb-6">
            Fayko ne peut pas vérifier automatiquement un paiement. Basez votre décision sur la référence de
            transaction fournie (à recouper avec l'opérateur mobile money ou la banque si besoin) et motivez
            toujours votre choix — la note est conservée avec le contrat.
        </div>

        <p class="text-sm text-slate-500 mb-6">{{ $contratsEnLitige->count() }} contrat(s) en litige.</p>

        @forelse ($contratsEnLitige as $contrat)
            <div class="bg-white rounded-2xl shadow-sm p-6 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="font-semibold text-slate-800">Contrat #{{ $contrat->id }} — {{ number_format($contrat->montant, 0, ',', ' ') }} FCFA</p>
                        <p class="text-sm text-slate-500">
                            Prêteur : {{ $contrat->preteur->prenom }} {{ $contrat->preteur->nom }} ({{ $contrat->preteur->telephone }})
                            — Emprunteur : {{ $contrat->emprunteur->prenom }} {{ $contrat->emprunteur->nom }} ({{ $contrat->emprunteur->telephone }})
                        </p>
                    </div>
                    <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">Litige</span>
                </div>

                @foreach ($contrat->remboursements as $remboursement)
                    <div class="border border-slate-200 rounded-xl p-4 mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-medium text-slate-800">{{ number_format($remboursement->montant, 0, ',', ' ') }} FCFA déclaré(s)</p>
                            <p class="text-xs text-slate-400">{{ $remboursement->date_declaration->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="text-sm text-slate-600 mb-1">
                            Déclaré par : {{ $remboursement->declarant->prenom }} {{ $remboursement->declarant->nom }} ({{ $remboursement->declarant->telephone }})
                        </p>
                        <p class="text-sm text-slate-600 mb-4">
                            Référence de transaction : <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $remboursement->reference_transaction }}</span>
                        </p>

                        <form method="POST" action="{{ route('admin.litiges.valider', $remboursement) }}" class="mb-2">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="motif_admin" placeholder="Motif de la décision (obligatoire)" required
                                       class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
                                <button type="submit" class="bg-emerald-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-emerald-700 whitespace-nowrap">
                                    Valider le remboursement
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.litiges.rejeter', $remboursement) }}">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="motif_admin" placeholder="Motif de la décision (obligatoire)" required
                                       class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
                                <button type="submit" class="bg-red-50 text-red-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-red-100 whitespace-nowrap">
                                    Rejeter la déclaration
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="text-center py-16 text-slate-400 text-sm">Aucun litige en cours.</div>
        @endforelse
    </main>
</body>
</html>