<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\BaseRepositoryInterface;
use App\Repositories\BaseRepository;

/**
 * Class BaseRepositoryServiceProvider
 *
 * Service provider for binding the BaseRepositoryInterface to the BaseRepository implementation.
 *
 * @package App\Providers
 */
class BaseRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the implementation of BaseRepositoryInterface to BaseRepository.
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // No additional bootstrapping is performed in this service provider.
    }
}
