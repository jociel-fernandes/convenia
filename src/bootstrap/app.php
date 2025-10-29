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
        $middleware->alias([
            'role.permission' => \App\Http\Middleware\RoleOrPermissionsMiddleware::class,
            'localization' => \App\Http\Middleware\LanguageMiddleware::class,
        ]);

        // Aplicar localização em todas as rotas da API
        $middleware->api(append: [
            \App\Http\Middleware\LanguageMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $detectLanguage = function ($request) {
            $supportedLocales = ['pt-BR', 'pt', 'en'];
            
            // Check query parameter first
            $locale = $request->query('lang');
            if ($locale && in_array($locale, $supportedLocales)) {
                return $locale;
            }
            
            $preferred = $request->getPreferredLanguage($supportedLocales);
            if ($preferred) {
                $locale = str_replace('_', '-', $preferred);
                return $locale === 'pt' ? 'pt-BR' : $locale;
            }
            
            return 'en';
        };

        // Customização resposta de validação para seguir padrão RESTful
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) use ($detectLanguage) {
            if ($request->expectsJson()) {
                $locale = $detectLanguage($request);
                app()->setLocale($locale);

                return response()->json([
                    'message' => $e->getMessage(),
                    'data' => null,
                    'code' => __('api.errors.validation_error'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Customização resposta de autenticação
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) use ($detectLanguage) {
            if ($request->expectsJson()) {
                
                $locale = $detectLanguage($request);
                app()->setLocale($locale);

                return response()->json([
                    'message' => __('api.auth.token_invalid'),
                    'data' => null,
                    'code' => __('api.errors.unauthenticated'),
                ], 401);
            }
        });
    })->create();
