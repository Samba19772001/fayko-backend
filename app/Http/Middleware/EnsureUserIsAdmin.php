<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Protège les routes du panneau admin : seuls les comptes avec is_admin=true
// peuvent y accéder. Redirige vers la connexion admin sinon.
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->is_admin) {
            return redirect()->route('admin.login')->with('erreur', 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}