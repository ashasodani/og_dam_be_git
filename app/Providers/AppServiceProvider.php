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
use Illuminate\Support\Facades\Request;


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
        try {
            $data = Permission::all()->pluck('name')->toArray();
            foreach ($data as $permission) {
                Gate::define(
                    $permission,
                    function ($user) use ($permission) {
                        $workspaceId = Request::header('X-Workspace-ID');
                        if(!$workspaceId){
                            $userPermission =  $user->getAllPermissions()->pluck('name')->toArray();
                            return (bool) in_array($permission, $userPermission);
                        } else {
                            $permissionServiceClass = $this->app->make(PermissionService::class);
                            $permissionNames = $permissionServiceClass->getAllPermissionWithWorkspace($user, $workspaceId);
                            return (bool) in_array($permission, $permissionNames->toArray());
                        }
                    }
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
