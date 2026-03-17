<?php
namespace App\Services;

use App\Models\InviteUsers;
use App\Models\User;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditing;

/**
 * Class UserService
 *
 * Service class for managing admin users, including retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class UserService
{
    /**
     * @var RoleService The service  for sync with admin user data.
     */
    protected $roleService;

    /**
     * @var UserRepository The repository for interacting with admin user data.
     */
    protected $userRepository;

    /**
     * @var InviteUserRepository The repository for interacting with admin user data.
     */
    protected $inviteUserRepository;

    /**
     * AdminUserService constructor.
     *
     * @param UserRepository $userRepository The repository for interacting with admin user data.
     */
    public function __construct(UserRepository $userRepository, RoleService $roleService, UserInvitationRepository $inviteUserRepository)
    {
        $this->userRepository       = $userRepository;
        $this->inviteUserRepository = $inviteUserRepository;
        $this->roleService          = $roleService;
    }

    /**
     * Get the query builder for admin users.
     *
     * @return Builder
     */
    public function getUsersQuery(): Builder
    {
        return $this->userRepository->query();
    }

    /**
     * Find an admin user by their ID.
     *
     * @param string $userId The ID of the admin user.
     *
     * @return Model|null The admin user model or null if not found.
     */
    public function findByUserId(string $userId): ?Model
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * Create a new admin user.
     *
     * @param array $userData The data for creating the admin user.
     *
     * @return Model The created admin user model.
     */
    public function createUser(array $userData): Model
    {
        //   dd($userData);
        $user = $this->userRepository->create($userData);
        //Change the status of invited user as accepted and make relationship with workspace
        if ($user) {
            $exists = InviteUsers::where('email', $user->email)->exists();
            if ($exists) {
                $data       = ['status' => 'in-review'];
                $inviteUser = $this->inviteUserRepository->updateInvitedData($user, $data);

                // Sync workspace with roles of invited user to registerd user
                $this->syncWorkspaceRoles($inviteUser, $user);
            }

        }

        return $user;
    }

    /**
     * Update an existing admin user.
     *
     * @param string $userUuid The UUID of the admin user to be updated.
     * @param array  $userData The data for updating the admin user.
     *
     * @return bool True on successful update, false otherwise.
     */
    public function updateUser(string $userUuid, array $userData): bool
    {
        return $this->userRepository->updateByUuid($userUuid, $userData);
    }

    /**
     * Delete an admin user by UUID.
     *
     * @param string $userUuid The UUID of the admin user to be deleted.
     *
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteUser(int $userId): bool
    {
        $email       = User::where('id', $userId)->value('email');
        if ($email) {
            $userInvited = InviteUsers::where('email', $email)->first();
    
            if ($userInvited) {
               // $this->inviteUserRepository->deleteByID($userInvited->id);
                $userInvited->delete(); // delete without audit
            }
        }
        $result = $this->userRepository->deleteByID($userId);

    return $result;
    }

    /**
     * Sync The user
     *
     * @param array $user.
     *
     * @return Model True on successful deletion, false otherwise.
     */
    public function syncWorkspaceRoles(InviteUsers $inviteUser, User $user): Model
    {
        // Sync global roles from inviteUser to user
        $user->syncRoles($inviteUser->roles);

        
        return $user;
    }

    /**
     * Assign Resources to the user
     *
     * @param array $userData.
     *
     * @return Model True on successful deletion, false otherwise.
     */
    public function asignUserResources(array $userData): Model
    {
        return User::first(); // Placeholder or handle differently if needed, but removing workspace logic
    }

    /**
     * Get the query Invited User array for user.
     *
     */
    public function getUserResourceCollection($request)
    {
        $relation             = $request->has('include') ? [$request->get('include')] : ['users'];
        $perPage              = request()->input('per_page', 10);
        $append               = $request->all();
        $append['per_page']   = $perPage ?? 10;
        $append['search']     = $append['search'] ?? '';
        $append['sort_by']    = $append['sort_by'] ?? 'created_at';
        $append['sort_order'] = $append['sort_order'] ?? 'desc';
        $columns              = ['*'];
        $searchColumns        = ['name', 'email'];
        $allowedSortBy        = ['name', 'created_at', 'email'];

        $query = User::where('email', '!=', 'superadmin@degeest.com');

        $query->when($append['search'], function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });
        $data = $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;

        // Relationship references removed
        return $data;
    }

    public function checkInvitedUser($data)
    {
        $emailExists = InviteUsers::where('email', $data)->exists();

        if ($emailExists) {
            return $data;
        } else {
            return false;
        }
    }
    public function checkUser($data)
    {
        $emailExists = User::where('email', $data)->exists();

        if ($emailExists) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Assign Resources to the user
     *
     * @param array $userData.
     *
     * @return Model True on successful deletion, false otherwise.
     */
    public function updateUserResources(int $userId, array $userData): Model
    {
        $user       = $this->userRepository->findById($userId);
        $user->name = $userData['name'];
        $user->save();
        
        return $user;
    }

    /**
     * Fetch the user profile with the given relationships.
     *
     * @param mixed $include The relationship(s) to include in the result.
     *
     * @return \Illuminate\Database\Eloquent\Collection The user profile.
     */
    public function getProfileDetails($userId)
    {
        $user = User::find($userId)->load('role:id,name'); // eager load role with id and name only

        $role = $user->roles->first();

        return [
            'id'        => $user->id,
            'email'     => $user->email,
            'full_name' => $user->name,
            'role'      => $role ? [
                'id'   => $role->id,
                'name' => $role->name,
            ] : null,
        ];
    }

    /**
     * Update the user profile with the given relationships.
     *
     * @param mixed $include The relationship(s) to include in the result.
     *
     * @return \Illuminate\Database\Eloquent\Collection The user profile.
     */
    public function updateProfileDetails($userId, $data)
    {
        return $this->userRepository->update($userId, $data);
    }

    public function deactivateUser($userId): ?User
    {
        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        // Update user status to 'inactive'
        $user->status = 'inactive';
        $user->save();

        // Update invite_users status to 'pending' if exists
        $invite = InviteUsers::where('email', $user->email)->first();
        if ($invite) {
            $invite->status = 'pending';
            $invite->save();
        }

        return $user;
    }

     public function notifyUserForOnboarding($data)
    {
        // Send in-app notification if enabled
        $title = "Invitation Accepted";
        $loginUser = $data;
        $message = "{$data->name} accepted their invite to Deegest";
        $type="invitation_accept";
        $url = env('APP_FE_URL')."user-management";
        //$url = json_encode($url, JSON_UNESCAPED_SLASHES);
      
        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $this->userRepository->notifyUser($superUser,
            $title,
            $message,
            $url,
            $type,
            $loginUser->name,
            true,
            true,
            $data->name);
        }
        return $data;
    }
}
