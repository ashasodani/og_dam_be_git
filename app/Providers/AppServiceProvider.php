<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Interfaces\LoginServiceInterface;
use App\Interfaces\LogoutServiceInterface;
use App\Services\LoginService;
use App\Services\LogoutService;
use App\Services\PermissionService;
use Spatie\Permission\Models\Permission;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the implementation of LoginServiceInterface to AdminLoginService.
        $this->app->bind(LoginServiceInterface::class, LoginService::class);

        // Bind the implementation of LogoutServiceInterface to AdminLogoutService.
        $this->app->bind(LogoutServiceInterface::class, LogoutService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        try {
            $data = Permission::all()->pluck('name')->toArray();
            foreach ($data as $permission) {
                Gate::define(
                    $permission,
                    function ($user) use ($permission) {
                        $userPermission =  $user->getAllPermissions()->pluck('name')->toArray();
                        return (bool) in_array($permission, $userPermission);
                    }
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
