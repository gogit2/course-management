<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Render every API error in the standard {success, message, data, errors} shape.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            // Already a finished response (e.g. BaseFormRequest, abort($response)).
            if ($e instanceof HttpResponseException) {
                return null;
            }

            $status = 500;
            $message = config('app.debug') ? $e->getMessage() : 'Server Error';
            $errors = null;
            $headers = [];

            if ($e instanceof AuthenticationException) {
                $status = 401;
                $message = 'Unauthenticated';
            } elseif ($e instanceof ValidationException) {
                $status = 422;
                $message = 'Validation Error';
                $errors = $e->errors();
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: (Response::$statusTexts[$status] ?? 'Error');
                $headers = $e->getHeaders();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ], $status, $headers);
        });
    })->create();
