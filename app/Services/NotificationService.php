<?php
namespace App\Services;

use App\Models\WorkspaceNotification;
use App\Models\Workspaces;
use App\Repositories\NotificationRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotificationService
 *
 * This class provides services related to roles, such as retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class NotificationService
{
    /**
     * @var NotificationRepository The repository for interacting with role data.
     */
    protected $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Build the role query
     *
     * @return Builder
     */
    public function getNotificationQuery(): Builder
    {
        return $this->notificationRepository->query();
    }

    /**
     * Find a role by its ID.
     *
     * @param int $roleId The UUID of the role.
     *
     * @return Model|null The role model or null if not found.
     */
    public function findByNotificationId(int $roleId): ?Model
    {
        return $this->notificationRepository->findById($roleId, ['*'], ['permissions']);
    }

    /**
     * Get all roles.
     *
     * @return Collection The collection of role models.
     */
    public function getAllNotifications(): Collection
    {
        return $this->notificationRepository->all();
    }

    /**
     * Create a new role.
     *
     * @param array $roleData The data for creating the role.
     *
     * @return Model The created role model.
     */
    public function createNotification(array $roleData): Model
    {
        return $this->notificationRepository->create($roleData);
    }

    /**
     * Update an existing role.
     *
     * @param int $roleId The UUID of the role to be updated.
     * @param array  $roleData The data for updating the role.
     *
     * @return Model True on successful update, false otherwise.
     */
    public function updateNotification(int $roleId, array $roleData): Model
    {
        return $this->notificationRepository->update($roleId, $roleData);
    }

    /**
     * Create multiple workspace notifications.
     *
     * @param array $data The input data.
     * @return array The created workspace notifications.
     */
    public function createWorkspaceNotification(array $data)
    {
        //dd($data);
        return WorkspaceNotification::updateOrCreate(
            [
                'workspace_id'  => $data['workspace_id'],
                'collection_id' => $data['collection_id'],
                'user_id'       => Auth::user()->id,
            ],
            [
                'workspace_id'  => $data['workspace_id'],
                'collection_id' => $data['collection_id'],
                'is_mail'       => $data['is_mail'],
                'in_app'        => $data['in_app'],
                'user_id'       => Auth::user()->id,
            ]
        );
    }

    /**
     * Get all workspace notifications by workspace ID.
     *
     * @param int $workspaceId The workspace ID.
     * @return Collection The collection of workspace notifications.
     */
    public function getAllWorkspaceNotifications($workspaceId)
    {
        return WorkspaceNotification::where('workspace_id', $workspaceId)->get();
    }
    /**
     * Get the query Collection for workspace.
     * @return Collection
     */
    public function getWorkspaceCollection($request)
    {
        $user          = Auth::user();
        $include       = $request->has('include') ? [$request->get('include')] : ['collections'];
        $result        = [];
        $workspaceData = [];
        if ($user->hasRole('Super Admin')) {
            $data = Workspaces::with($include)->whereHas('collections')->get();
        } else {
            $data = []; // user_workspaces removed
        }

        foreach ($data as $workspace) {
            foreach ($workspace->collections as $collection) {
                $notifyData = WorkspaceNotification::where('workspace_id', $workspace->id)
                    ->where('collection_id', $collection->id)->where('user_id', $user->id)->first();
                $result[] = [
                    'collection_id'   => $collection->id,
                    'collection_name' => $collection->name,
                    'is_email'        => $notifyData?->is_mail ?? null,
                    'in_app'          => $notifyData?->in_app ?? null,
                ];
            }
            $workspaceData[] = [
                'workspace_id'   => $workspace->id,
                'workspace_name' => $workspace->name,
                'collections'    => $result,
            ];
            $result = [];
        }
        return $workspaceData;
    }
}
