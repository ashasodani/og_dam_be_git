<?php

namespace App\Services;

use App\Repositories\UserInvitationRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\InviteUsers;
use App\Services\RoleService;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInvitation;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Class UserService
 *
 * Service class for managing admin users, including retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class UserInvitationService
{
    /**
     * @var RoleService The service  for sync with admin user data.
     */
    protected $roleService;

    /**
     * @var inviteUserRepository The repository for interacting with admin user data.
     */
    protected $inviteUserRepository;

    /**
     * AdminUserService constructor.
     *
     * @param UserInvitationRepository $inviteUserRepository The repository for interacting with admin user data.
     */
    public function __construct(UserInvitationRepository $inviteUserRepository, RoleService $roleService)
    {
        $this->inviteUserRepository = $inviteUserRepository;
        $this->roleService = $roleService;
    }

    /**
     * Get the query builder for admin users.
     *
     * @return Builder
     */
    public function getUsersQuery(): Builder
    {
        return $this->inviteUserRepository->query();
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
        return $this->inviteUserRepository->findById($userId);
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
        return $this->inviteUserRepository->create($userData);
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
        return $this->inviteUserRepository->updateByUuid($userUuid, $userData);
    }

    /**
     * Delete an invited user by id.
     *
     * @param string $invitedUserId The ID of the invited user to be deleted.
     *
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteInvitedUser(string $invitedUserId): bool
    {
        return $this->inviteUserRepository->deleteByID($invitedUserId);
    }

  
    /**
     * Invite The user
     *
     * @param array $userData.
     *
     * @return Model True on successful deletion, false otherwise.
     */
    public function inviteUser(array $userData): Model
    {
        $inviteUser = $this->inviteUserRepository->create($userData);
        if ($inviteUser) {
            if (isset($userData['role_id'])) {
                $role = Role::findById((int)$userData['role_id'], 'sanctum');
                $inviteUser->syncRoles($role);
            }
            $this->mailSendUser($inviteUser);
        }
        return $inviteUser;
    }

    
      /**
     * Get the query Invited User array for user.
     *
     */
    public function getInvitedUserCollection($request)
    {
        $relation = $request->has('include') ? array($request->get('include')) : ['users'];
        $perPage = request()->input('per_page', 10);
        $append = $request->all();
        $append['per_page'] = $perPage??10;
        $append['search'] = $append['search']??'';
        $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';
        $columns = ['*'];
        $searchColumns = ['email'];
        $allowedSortBy = ['created_at', 'email'];

        $query =  InviteUsers::where('status', 'pending');
        $query->when($append['search'], function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });
        $data= $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }

     /**
     * mail send to invited user
     *
     * @param array $userData.
     *
     * 
     */
    public function mailSendUser($inviteUser) 
    {
        //$hashedToken = Hash::make($inviteUser->email);
        $encodedName = base64_encode($inviteUser->email);
        $template = [
            'name' => $inviteUser->email,
            'url' => env("APP_FE_URL").'auth/onboard/'.$encodedName
        ];
        $inviteUser->invite_link=$template['url'];
        $inviteUser->save();
        return Mail::to($inviteUser->email)->send(new UserInvitation($template));
    }

     /**
     * Resend Invite to invited user
     *
     * @param array $userData The data for resending the invitation to the user.
     *
     * @return Model The invited user model.
     */
    public function resendInviteUser(array $userData): Model
    {
        $inviteUser = InviteUsers::where('email', $userData['email'])
        ->where('status', 'pending')
        ->first();
        if($inviteUser){
            $mailSend = $this->mailSendUser($inviteUser);
        }
        return $inviteUser;
    }
}
