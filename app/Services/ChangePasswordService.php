<?php

namespace App\Services;

use App\Interfaces\PasswordChangeInterface;
use App\Enums\AdminEnum;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Class AdminChangePasswordService
 *
 * This class provides services for changing the password of the admin user.
 *
 * @package App\Services
 */
class AdminChangePasswordService implements PasswordChangeInterface
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * AdminChangePasswordService constructor.
     *
     * @param AdminUserRepository $adminUserRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Change the password for the authenticated admin user.
     *
     * @param array $request
     * @return bool
     */
    public function changePassword($request)
    {
        $userId = Auth::guard(AdminEnum::GuardName->value)->user()->id;
        $data = ['password' => $request['new_password']];
        $this->userRepository->update($userId, $data);
        return true;
    }
}
