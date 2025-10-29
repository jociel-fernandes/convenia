<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Contracts\CollaboratorRepositoryInterface;
use App\Models\User;
use App\Observers\UserObserver;
use App\Repositories\UserRepository;
use App\Repositories\CollaboratorRepository;
use App\Services\UserService;
use App\Services\CollaboratorService;
use App\Services\CollaboratorImportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CollaboratorRepositoryInterface::class, CollaboratorRepository::class);

        // Register services
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService($app->make(UserRepositoryInterface::class));
        });

        $this->app->singleton(CollaboratorService::class, function ($app) {
            return new CollaboratorService($app->make(CollaboratorRepositoryInterface::class));
        });

        $this->app->singleton(CollaboratorImportService::class, function ($app) {
            return new CollaboratorImportService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register User Observer for native model events
        User::observe(UserObserver::class);
    }
}
