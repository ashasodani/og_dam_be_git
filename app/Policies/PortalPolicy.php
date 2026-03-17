<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Portals;

class PortalPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        
        return $user->can('portals-list');  
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Portals $portals): bool
    {
       return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('portals-create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Portals $portals): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Portals $portals): bool
    {
        return false;
    }

}
