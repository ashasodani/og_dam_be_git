<?php

namespace App\Services;

use App\Interfaces\LoginServiceInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Class LoginService
 *
 * This class provides services for authenticating admin users.
 *
 * @package App\Services
 */
class LoginService implements LoginServiceInterface
{
    /**
     * Attempt to log in the admin user with the provided credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function login($credentials, $remember): bool
    {
        if(Auth::guard('web')->attempt($credentials, $remember)){
            return true; // Authentication successful
        }

        return false; // Authentication failed
    }

     /**
     * get user
     *
     * @param void
     */
    public function getUser()
    {
        return Auth::user();
    }
}
