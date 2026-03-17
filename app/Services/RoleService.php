<?php
namespace App\Services;

use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use app\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class RoleService
 *
 * This class provides services related to roles, such as retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class RoleService
{
    /**
     * @var RoleRepository The repository for interacting with role data.
     */
    protected $roleRepository;

    /**
     * @var UserRepository The repository for interacting with role data.
     */
    protected $userRepository;

    /**
     * RoleService constructor.
     *
     * @param RoleRepository $roleRepository The repository for interacting with role data.
     */
    public function __construct(RoleRepository $roleRepository, UserRepository $userRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Build the role query
     *
     * @return Builder
     */
    public function getRoleQuery(): Builder
    {
        return $this->roleRepository->query();
    }

    /**
     * Get the query Collection for section.
     * @return LengthAwarePaginator
     */
    public function getRoleCollection($request): LengthAwarePaginator
    {
       // dd($include);
       return $this->roleRepository->getRoleData($request);
    }

    /**
     * Find a role by its ID.
     *
     * @param int $roleId The UUID of the role.
     *
     * @return Model|null The role model or null if not found.
     */
    public function findByRoleId(int $roleId): ?Model
    {
        return $this->roleRepository->findById($roleId, ['*'], ['permissions']);
    }

    /**
     * Get all roles.
     *
     * @return Collection The collection of role models.
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->all();
    }

    /**
     * Create a new role.
     *
     * @param array $roleData The data for creating the role.
     *
     * @return Model The created role model.
     */
    public function createRole(array $roleData): Model
    {
        return $this->roleRepository->create($roleData);
    }

    /**
     * Update an existing role.
     *
     * @param int $roleId The UUID of the role to be updated.
     * @param array  $roleData The data for updating the role.
     *
     * @return Model True on successful update, false otherwise.
     */
    public function updateRole(int $roleId, array $roleData): Model
    {
        return $this->roleRepository->update($roleId, $roleData);
    }

    /**
     * Create a role with its associated permissions.
     *
     * @param array $roleData      The data for updating the role.
     * @param array $permissionIds The IDs of the associated permissions.
     *
     * @return Model The updated role model.
     */
    public function createRoleWithPermissions(array $roleData, array $permissionIds): Model
    {
        $roleData = $this->createRole($roleData);
        $this->syncPermissions($roleData->id, $permissionIds);
        return $this->findByRoleId($roleData->id);
    }

    /**
     * Update a role with its associated permissions.
     *
     * @param int $roleId      The UUID of the role to be updated.
     * @param array  $roleData      The data for updating the role.
     * @param array  $permissionIds The IDs of the associated permissions.
     *
     * @return Model The updated role model.
     */
    public function updateRoleWithPermissions(int $roleId, array $roleData, array $permissionIds): Model
    {
        $roleData = $this->updateRole($roleId, $roleData);
        $this->syncPermissions($roleData->id, $permissionIds);
        $this->invalidateRoleUsersTokens($roleData);
        return $this->findByRoleId($roleData->id);
    }

    public function invalidateRoleUsersTokens(Role $role)
    {
        // Get all users with this role
         $users = User::whereHas('roles', function ($query)use($role) {
            $query->where('name', $role->name);
        })->get();
       
        foreach ($users as $user) {
            $user->tokens()->delete(); // deletes all tokens for this user
        }
    }

    /**
     * Delete a role by ID.
     *
     * @param int $roleId The UUID of the role to be deleted.
     *
     * @return bool True on successful deletion, false otherwise.
     *
     * @note This method will detach all associated permissions and users before deleting the role.
    */
    public function deleteRole(int $roleId)
    {
        $role = Role::findById($roleId);
        $roleUsers = $role->users->count();
        if($roleUsers > 0){
            return false;
        }
        $exists = \DB::table('invite_users_workspace')
        ->where('role_id', $roleId)
        ->exists();
          //dd($exists);
        if ($exists) {
            return false;
        }
        if (! $role) {
            return false;
        }

        $role->permissions()->detach();
        $role->users()->detach();
        return $role->delete();
    }

    /**
     * Sync permissions for a role.
     *
     * @param string $roleUuid      The UUID of the role.
     * @param array  $permissionIds The IDs of the associated permissions.
     *
     * @return Model The role model after syncing permissions.
     */
    public function syncPermissions(int $roleId, array $permissionIds): Model
    {
        $role = $this->roleRepository->findById($roleId);
        $role->permissions()->sync($permissionIds);
        return $role;
    }
    /**
     * Sync Role for a user.
     *
     * @param int $roleId      The ID of the role.
     * @param int  $userid The IDs of the associated permissions.
     *
     * @return Model The role model after syncing permissions.
     */
    public function syncRole(int $roleId, int $userid): Model
    {
        $role = $this->roleRepository->findById($roleId);
        $user = $this->userRepository->findById($userid);
        $user->syncRoles($role);
        return $role;
    }
}
