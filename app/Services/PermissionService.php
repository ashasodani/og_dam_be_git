<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\permission;
use App\Models\Workspaces;
use Spatie\Permission\Models\Role;

/**
 * Class PermissionService
 *
 * This class provides services related to permissions, such as retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class PermissionService
{
    /**
     * @var PermissionRepository The repository for interacting with permission data.
     */
    protected $permissionRepository;

    /**
     * PermissionService constructor.
     *
     * @param PermissionRepository $permissionRepository The repository for interacting with permission data.
     */
    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Get all permissions.
     *
     * @return Collection All permissions.
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissionRepository->all();
    }

    /**
     * Get all permissions with associated roles.
     *
     * @return Collection All permissions with associated roles.
     */
    public function getAllPermissionWithRoles(): Collection
    {
        return Permission::with('roles')->all();
        //return $this->permissionRepository->all(['*'], ['roles']);
    }

    /**
     * Get all permissions with associated user and roles.
     *
     * 
     */
    public function getAllPermissionWithWorkspace($user, $workspaceId)
    {
        $role = $user->roles->first();
        return $permissionNames = $role ? $role->permissions->pluck('name') : collect();
    }
}
