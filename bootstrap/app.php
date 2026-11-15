<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PDOException $exception, Request $request) {
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $connectionError = str_starts_with((string) $exception->getCode(), '08')
                || in_array($driverCode, [1040, 1042, 1045, 2002, 2003, 2006, 2013], true)
                || in_array((int) $exception->getCode(), [1040, 1042, 1045, 2002, 2003, 2006, 2013], true);

            if (! $connectionError) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'La base de datos no está disponible temporalmente. Intenta de nuevo en unos momentos.'], 503)
                    ->header('Retry-After', '30');
            }

            return response()->view('errors.database-unavailable', [], 503)
                ->header('Retry-After', '30');
        });
    })->create();
