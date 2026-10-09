<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\RecordPageView;
use App\Models\Redirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'page-view' => RecordPageView::class,
        ]);

        // Mail clients post one-click unsubscribes without a CSRF token (RFC 8058);
        // the signed URL protects these routes instead.
        $middleware->validateCsrfTokens(except: ['bildirimler/kapat/*', 'takip/birak/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An address that changed (a renamed slug) answers with a permanent redirect.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->isMethod('GET') || $request->expectsJson()) {
                return null;
            }

            $target = Redirect::target($request->path());

            return $target !== null ? redirect($target, 301) : null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
