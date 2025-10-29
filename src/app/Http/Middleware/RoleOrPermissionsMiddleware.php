<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleOrPermissionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage examples:
     * Route::middleware(['role.permission:manager'])->group(...) // Check role
     * Route::middleware(['role.permission:manage users'])->group(...) // Check permission
     * Route::middleware(['role.permission:manager,collaborator'])->group(...) // Check multiple roles
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$rolesOrPermissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => __('api.auth.access_denied_unauthenticated'),
                'data' => null,
                'code' => __('api.errors.unauthenticated'),
            ], 401);
        }

        // Se não foi passado nenhum parâmetro, apenas verifica se está autenticado
        if (empty($rolesOrPermissions)) {
            return $next($request);
        }

        // Verificar se o usuário tem pelo menos uma das roles ou permissions
        foreach ($rolesOrPermissions as $roleOrPermission) {
            // Primeiro tenta como role
            if ($user->hasRole($roleOrPermission)) {
                return $next($request);
            }

            // Depois tenta como permission
            if ($user->can($roleOrPermission)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => __('api.auth.access_denied_insufficient_privileges'),
            'data' => null,
            'code' => __('api.errors.access_denied'),
        ], 403);
    }
}
