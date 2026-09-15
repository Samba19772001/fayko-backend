<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fayko — Administration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-sm w-full max-w-sm">
        <h1 class="text-xl font-semibold text-slate-800 mb-1">Fayko — Administration</h1>
        <p class="text-sm text-slate-500 mb-6">Connexion réservée aux agents autorisés.</p>

        @if (session('erreur'))
            <div class="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4">{{ session('erreur') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                <input type="text" name="telephone" value="{{ old('telephone') }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                <input type="password" name="password" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
            </div>
            <button type="submit"
                    class="w-full bg-slate-800 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-slate-900">
                Se connecter
            </button>
        </form>
    </div>
</body>
</html>