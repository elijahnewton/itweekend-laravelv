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
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = config('app.trusted_proxies');

        if ($trustedProxies === '*') {
            $middleware->trustProxies(at: '*');
        } elseif (is_string($trustedProxies)) {
            $proxyList = array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
            if ($proxyList !== []) {
                $middleware->trustProxies(at: $proxyList);
            }
        }

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
