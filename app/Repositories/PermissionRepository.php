<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Repositories\BaseRepository;

/**
 * Class PermissionRepository
 *
 * This class represents the repository for handling database operations related to permissions.
 *
 * @package App\Repositories
 */
class PermissionRepository extends BaseRepository
{
    /**
     * PermissionRepository constructor.
     *
     * @param Permission $permissionModel The model associated with this repository.
     */
    public function __construct(Permission $permissionModel)
    {
        $this->model = $permissionModel;
    }
}
