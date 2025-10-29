<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Collaborator;
use App\Policies\UserPolicy;
use App\Policies\CollaboratorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Collaborator::class => CollaboratorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // ✅ Configurar Laravel Passport para tokens JWT
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        
        // ✅ Configurar scopes para controle de acesso
        Passport::tokensCan([
            'users-read' => 'Read user information',
            'users-write' => 'Create and update users',
            'users-delete' => 'Delete users',
            'admin' => 'Full administrative access',
        ]);

        // ✅ Scopes padrão para personal access tokens
        Passport::setDefaultScope([
            'users-read',
            'users-write',
        ]);
    }
}
