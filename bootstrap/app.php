<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Si el formulario de ingreso quedó abierto hasta que expiró la sesión, se vuelve
        // a mostrar con un aviso en lugar de la página de error 419.
        // (Laravel ya convirtió el TokenMismatchException en un HttpException 419.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->is('ingresar')) {
                return redirect()->route('login')
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'La página estuvo abierta mucho tiempo. Vuelve a intentarlo.']);
            }
        });
    })->create();
