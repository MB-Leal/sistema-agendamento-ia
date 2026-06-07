<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 🎯 DEFINIÇÃO DE REDIRECIONAMENTO GLOBAL
        // Isso garante que após o login, o Laravel aponte para a seleção de módulos
        $middleware->redirectTo(
            guests: '/login',
            users: '/select-modules'
        );

        // 🛡️ REGISTRO DE ALIASES DE MIDDLEWARE
        $middleware->alias([
            'gestor' => \App\Http\Middleware\IsGestor::class,
            'role'   => \App\Http\Middleware\CheckRole::class, // ✅ Adicionado para resolver o erro 500
        ]);
        // 💬 EXCEÇÃO DO CSRF PARA AUTOMAÇÃO DO WHATSAPP
        // Substitua pelo path exato da rota que receberá os dados da API
        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp', 
            'api/webhook/whatsapp',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();