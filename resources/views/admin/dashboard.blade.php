<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fayko — Vérifications d'identité</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-slate-800">Fayko — Vérifications d'identité</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">Se déconnecter</button>
        </form>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-8">
        @if (session('succes'))
            <div class="bg-emerald-50 text-emerald-700 text-sm rounded-lg p-3 mb-6">{{ session('succes') }}</div>
        @endif
        @if (session('erreur'))
            <div class="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-6">{{ session('erreur') }}</div>
        @endif

        <p class="text-sm text-slate-500 mb-6">{{ $enAttente->count() }} compte(s) en attente de validation.</p>

        @forelse ($enAttente as $user)
            <div class="bg-white rounded-2xl shadow-sm p-6 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $user->prenom }} {{ $user->nom }}</p>
                        <p class="text-sm text-slate-500">{{ $user->telephone }} — inscrit le {{ $user->created_at->format('d/m/Y') }}</p>
                    </div>
                    <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">En attente</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 mb-2">CNI — Recto</p>
                        <img src="{{ Storage::disk('public')->url($user->cni_recto_url) }}" class="rounded-lg border border-slate-200 w-full object-cover" style="max-height: 220px;">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 mb-2">CNI — Verso</p>
                        <img src="{{ Storage::disk('public')->url($user->cni_verso_url) }}" class="rounded-lg border border-slate-200 w-full object-cover" style="max-height: 220px;">
                    </div>
                </div>

                <div class="flex gap-3">
                    <form method="POST" action="{{ route('admin.verifications.valider', $user) }}">
                        @csrf
                        <button type="submit" class="bg-emerald-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-emerald-700">
                            Valider
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.verifications.rejeter', $user) }}">
                        @csrf
                        <button type="submit" class="bg-red-50 text-red-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-red-100">
                            Rejeter
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-slate-400 text-sm">Aucune vérification en attente.</div>
        @endforelse
    </main>
</body>
</html>