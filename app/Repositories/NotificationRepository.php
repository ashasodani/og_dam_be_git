<?php

namespace App\Repositories;

use App\Models\Workspaces;
use App\Repositories\BaseRepository;

/**
 * Class NotificationRepository
 *
 * This class represents the repository for handling database operations related to roles.
 *
 * @package App\Repositories
 */
class NotificationRepository extends BaseRepository
{
    /**
     * RoleRepository constructor.
     *
     * @param Notification $roleModel The model associated with this repository.
     */
    public function __construct(Workspaces $roleModel)
    {
        $this->model = $roleModel;
    }
}
