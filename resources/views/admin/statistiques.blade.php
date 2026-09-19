<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fayko — Statistiques</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <h1 class="text-lg font-semibold text-slate-800">Fayko — Statistiques</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800">Vérifications d'identité</a>
            
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">Se déconnecter</button>
        </form>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <a href="{{ route('admin.statistiques', ['date_debut' => now()->format('Y-m-d'), 'date_fin' => now()->format('Y-m-d')]) }}"
               class="text-xs font-medium bg-white border border-slate-300 rounded-full px-3 py-1.5 hover:bg-slate-50">Aujourd'hui</a>
            <a href="{{ route('admin.statistiques', ['date_debut' => now()->startOfMonth()->format('Y-m-d'), 'date_fin' => now()->format('Y-m-d')]) }}"
               class="text-xs font-medium bg-white border border-slate-300 rounded-full px-3 py-1.5 hover:bg-slate-50">Ce mois-ci</a>
            <a href="{{ route('admin.statistiques', ['date_debut' => now()->startOfYear()->format('Y-m-d'), 'date_fin' => now()->format('Y-m-d')]) }}"
               class="text-xs font-medium bg-white border border-slate-300 rounded-full px-3 py-1.5 hover:bg-slate-50">Cette année</a>

            <form method="GET" action="{{ route('admin.statistiques') }}" class="flex items-center gap-2 ml-auto">
                <input type="date" name="date_debut" value="{{ $dateDebut->format('Y-m-d') }}"
                       class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs">
                <span class="text-slate-400 text-xs">à</span>
                <input type="date" name="date_fin" value="{{ $dateFin->format('Y-m-d') }}"
                       class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs">
                <button type="submit" class="bg-slate-800 text-white text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-slate-900">
                    Filtrer
                </button>
            </form>
        </div>

        <p class="text-xs text-slate-400 mb-4">
            Période : {{ $dateDebut->format('d/m/Y') }} → {{ $dateFin->format('d/m/Y') }}
        </p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs text-slate-500 mb-1">Contrats créés</p>
                <p class="text-2xl font-semibold text-slate-800">{{ $contratsCrees }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs text-slate-500 mb-1">Contrats conclus</p>
                <p class="text-2xl font-semibold text-slate-800">{{ $contratsConclus }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs text-slate-500 mb-1">Frais collectés</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ number_format($fraisCollectes, 0, ',', ' ') }} F</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs text-slate-500 mb-1">Volume total prêté</p>
                <p class="text-2xl font-semibold text-slate-800">{{ number_format($volumePrete, 0, ',', ' ') }} F</p>
            </div>
        </div>

        <h2 class="text-sm font-semibold text-slate-700 mb-3">12 derniers mois</h2>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Mois</th>
                        <th class="text-right px-5 py-3 font-medium">Contrats créés</th>
                        <th class="text-right px-5 py-3 font-medium">Contrats conclus</th>
                        <th class="text-right px-5 py-3 font-medium">Frais collectés</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historiqueMensuel as $ligne)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3 text-slate-700">{{ $ligne['label'] }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">{{ $ligne['contrats_crees'] }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">{{ $ligne['contrats_conclus'] }}</td>
                            <td class="px-5 py-3 text-right text-emerald-700 font-medium">{{ number_format($ligne['frais_collectes'], 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>