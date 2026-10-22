<?php

use App\Http\Middleware\AutorizarBiblioteca;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['acceso.password', 'acceso.password_confirmation']);
        $middleware->alias(['biblioteca' => AutorizarBiblioteca::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route(auth()->user()?->rol === 'USUARIO' ? 'libros.index' : 'dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['acceso.password', 'acceso.password_confirmation']);
    })->create();
