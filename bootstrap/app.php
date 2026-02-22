<?php

use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\SanitizeRequestLogging;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottleWithCaptcha;
use Illuminate\Database\QueryException;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(SanitizeRequestLogging::class);
        $middleware->prepend(ForceHttps::class);
        $middleware->append(SetLocale::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: SetLocale::class,
        );

        $middleware->alias([
            'throttle.captcha' => ThrottleWithCaptcha::class,
            'no.cache' => NoCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $e) {
            return response()->view('errors.minimal', [
                'code' => 500,
                'title' => __('Erreur serveur'),
                'message' => __('Une erreur interne s\'est produite. Veuillez réessayer plus tard.'),
            ], 500);
        });
    })->create();
