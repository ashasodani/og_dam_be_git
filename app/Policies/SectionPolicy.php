<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sections;

class SectionPolicy
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
        
        return $user->can('section-list');  
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, sections $section): bool
    {
        return $user->can('sections-view');  
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('sections-create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, sections $section): bool
    {
        return $user->can('sections-update');  
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, sections $section): bool
    {
        return $user->can('sections-delete');  
    }
}
