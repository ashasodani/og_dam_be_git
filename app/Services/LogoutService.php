<?php

namespace App\Services;

use App\Interfaces\LogoutServiceInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Class LogoutService
 *
 * This class provides services for logging out admin users.
 *
 * @package App\Services
 */
class LogoutService implements LogoutServiceInterface
{
    /**
     * Log out the authenticated user.
     *
     * @return void
     */
    public function logout()
    {
        return Auth::user()->currentAccessToken()->delete();
    }   
}
