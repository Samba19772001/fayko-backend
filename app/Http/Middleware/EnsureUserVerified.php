<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->estVerifie()) {
            return response()->json([
                'message' => "Compte non vérifié : impossible d'effectuer cette action tant que votre identité (CNI) n'a pas été validée.",
            ], 403);
        }

        return $next($request);
    }
}