<?php

namespace App\Providers;

use App\Contracts\CollaboratorRepositoryInterface;
use App\Repositories\CollaboratorRepository;
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
        $this->app->bind(CollaboratorRepositoryInterface::class, CollaboratorRepository::class);

        // Register services
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
        // Observer removido junto com o CRUD de usuários
    }
}
