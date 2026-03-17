<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspaces;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkspacesPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
     //  dd("p");
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
       dd("2",$permissions = $user->getAllPermissions(),$user->can('workspace_list'));
        return $user->can('workspace_list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workspaces $workspace): bool
    {
        dd("yes");
        dd($permissions = $user->getAllPermissions(),$user->can('workspace_view'));
        $permissions = $user->getAllPermissions();
        return $user->can('workspace_view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
       // dd("ion");
      // dd($user,$permissions = $user->getAllPermissions(),$user->can('workspace_create'));
        $permissions = $user->getAllPermissions();
        return $user->can('workspace_create');
       
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workspaces $workspaces): bool
    {
        dd($user,$permissions = $user->getAllPermissions(),$user->can('workspace_update'));
        return $user->can('workspace_update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, workspaces $workspace): bool
    {
        dd("ko");
     //   dd($user,$permissions = $user->getAllPermissions(),$user->can('workspace_update'));
        //return $user->can('workspace_delete');
    }

}
