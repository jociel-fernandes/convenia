<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Observers\UserObserver;
use App\Repositories\UserRepository;
use App\Services\UserService;
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

        // Register services
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService($app->make(UserRepositoryInterface::class));
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
