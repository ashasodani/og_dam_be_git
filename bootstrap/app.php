<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([ // thiss
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]); 
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function(ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        });
        $exceptions->render(function(NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        });
        $exceptions->render(function(AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        });

        $exceptions->render(function(ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        });
        $exceptions->render(function(AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action',
                'errors' => 'You are not authorized to perform this action',
                'error_code' => 401
            ], 401);
        });
        $exceptions->render(function(MethodNotAllowedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Method is not allowed for the requested route',
                'error_code' => 405
            ], 405);
        });
        $exceptions->render(function(RouteNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action',
                'errors' => 'You are not authorized to perform this action',
                'error_code' => 403
            ], 403);
        });
        $exceptions->render(function(\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
                'error_code' => 500
            ], 500);
        });
    })->create();
