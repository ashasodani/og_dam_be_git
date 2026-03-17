<?php

namespace App\Repositories;

use App\Models\Portals;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspaces;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Class DashboardRepository
 *
 * Repository class for handling user workspaces and portals data.
 *
 * @package App\Repositories
 */
class DashboardRepository extends BaseRepository
{
    /**
     * @var User The User Eloquent model instance.
     */
    protected $userModel, $portalModel, $workspaceModel, $roleModel;

    /**
     * DashboardRepository constructor.
     *
     * @param User $userModel
     */
    public function __construct(User $userModel, Role $roleModel, Workspaces $workspaceModel, Portals $portalModel)
    {
        $this->userModel      = $userModel;
        $this->roleModel      = $roleModel;
        $this->workspaceModel = $workspaceModel;
        $this->portalModel    = $portalModel;
    }

    /**
     * Fetch user with workspaces and portals along with role names.
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    public function getUserWorkspacesAndPortals($request, $user): array
    {
        // Load relations with pivot
        $workspaces = [];
        $portals = [];


        return [
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'workspaces' => $workspaces,
            'portals'    => $portals,
        ];
    }
}
