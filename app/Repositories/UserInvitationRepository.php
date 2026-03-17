<?php

namespace App\Repositories;

use App\Models\InviteUsers;
use App\Models\User;
use App\Repositories\BaseRepository;

/**
 * Class AdminUserRepository
 *
 * Repository class for interacting with the `AdminUser` model.
 *
 * @package App\Repositories
 */
class UserInvitationRepository extends BaseRepository
{
    /**
     * AdminUserRepository constructor.
     *
     * @param InviteUsers $model The underlying model for the repository.
     */
    public function __construct(InviteUsers $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new model and persist it to the database.
     *
     * @param array $payload The data for the new model.
     *
     * 
     */
    public function updateInvitedData(User $user, array $payload)
    {
        $invitedUser = $this->model->select('*')->with(['roles'])->where('email', $user->email)
            ->firstOrFail();
        $invitedUser->update(['status' => "in-review"]);
        return $invitedUser;
    }
}
