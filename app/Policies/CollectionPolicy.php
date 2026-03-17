<?php
namespace App\Policies;

use App\Models\Collections;
use App\Models\User;

class CollectionPolicy
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

        return $user->can('collections-list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Collections $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('collections-create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Collections $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Collections $workspace): bool
    {
        return false;
    }
}
