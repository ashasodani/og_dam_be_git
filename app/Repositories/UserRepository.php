<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\BaseRepository;

/**
 * Class AdminUserRepository
 *
 * Repository class for interacting with the `AdminUser` model.
 *
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    /**
     * AdminUserRepository constructor.
     *
     * @param AdminUser $model The underlying model for the repository.
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    
}
