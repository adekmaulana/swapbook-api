<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['prefix' => 'api/v1'], ['middleware' => ['auth:sanctum']]);

        require base_path('routes/channels.php');
    }
}
