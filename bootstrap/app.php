<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Point d'entrée Laravel 11 : ici on déclare les routes du projet et les
// middlewares globaux. Rien de "métier" Fayko ici, juste le câblage de base.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 'verifie' = notre middleware maison qui bloque les actions
        // sensibles (signer un contrat, etc.) aux comptes dont l'identité
        // n'a pas été vérifiée (§3.2 du cahier des charges).
        $middleware->alias([
            'verifie' => \App\Http\Middleware\EnsureUserVerified::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
