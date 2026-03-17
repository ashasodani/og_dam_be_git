<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserService
 *
 * Service class for managing admin users, including retrieval, creation, updating, and deletion.
 *
 * @package App\Services
 */
class UserChangePasswordService
{
    /**
     * @var UserRepository The repository for interacting with admin user data.
     */
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function changePassword(array $data): bool | string
    {
        $user = Auth::user();

        if (! Hash::check($data['old_password'], $user->password)) {
            return 'invalid_current_password';
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return true;
    }
}
