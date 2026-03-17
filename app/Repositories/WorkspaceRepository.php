<?php

namespace App\Repositories;

use App\Models\Workspaces;
use App\Repositories\BaseRepository;

/**
 * Class WorkspaceRepository
 *
 * Repository class for interacting with the `WORKSPACE` model.
 *
 * @package App\Repositories
 */
class WorkspaceRepository extends BaseRepository
{
    /**
     * WorkspaceRepository constructor.
     *
     * @param workspace $model The underlying model for the repository.
     */
    public function __construct(Workspaces $model)
    {
        $this->model = $model;
    }
}
