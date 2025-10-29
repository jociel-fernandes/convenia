<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return ApiResponse::handle(function () use ($request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return ApiResponse::unauthorized(__('api.auth.invalid_credentials'), 'INVALID_CREDENTIALS');
            }

            // Regra de negócio: Somente usuários com role 'manager' podem realizar login
            if (! $user->isManager()) {
                return ApiResponse::forbidden(__('api.auth.access_denied_managers_only'), 'ACCESS_DENIED');
            }

            // ✅ Passport: Criar token JWT simples
            $token = $user->createToken('API Token')->accessToken;

            return new AuthResource($user, $token);
        });
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        return ApiResponse::handle(function () use ($request) {
            // ✅ Passport: Revogar token atual
            $request->user()->token()->revoke();

            return ApiResponse::success([], __('api.auth.logout_success'));
        });
    }

    /**
     * Get current user
     */
    public function me(Request $request): UserProfileResource
    {
        return new UserProfileResource($request->user());
    }

    /**
     * Determinar scopes baseados nas permissões do usuário
     */
    private function getUserScopes(User $user): array
    {
        $scopes = ['users-read']; // Scope básico

        if ($user->hasPermissionTo('manage users') || $user->hasRole('manager')) {
            $scopes[] = 'users-write';
            $scopes[] = 'users-delete';
        }

        if ($user->hasRole('admin')) {
            $scopes[] = 'admin';
        }

        return $scopes;
    }
}
