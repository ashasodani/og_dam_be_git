<?php

namespace App\Services;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Class AdminResetPasswordService
 *
 * This class provides services for handling the password reset functionality for admin users.
 *
 * @package App\Services
 */
class AdminResetPasswordService
{
    /**
     * Check if the password reset token is expired for the provided user credentials and token.
     *
     * @param array $credentials The user credentials (e.g., email) and the reset token.
     * @param string $token The password reset token.
     *
     * @return bool|string False if the token is valid, otherwise a string representing the error message.
     */
    public function checkTokenExpired($credentials, $token)
    {

        if (is_null($user = Password::broker('admins')->getUser($credentials))) {
            return trans(Password::INVALID_USER);
        }

        if (!Password::broker('admins')->tokenExists($user, $token)) {
            return trans(Password::INVALID_TOKEN);
        }

        return false;
    }

     /**
     * Reset the password for the admin user based on the provided reset request.
     *
     * @param mixed $resetPasswordRequest The request object containing email, password, confirmation, and token.
     *
     * @return string The status of the password reset operation.
     */
    public function resetPassword($resetPasswordRequest)
    {
        $broker = 'admins';

        return Password::broker($broker)->reset(
            $resetPasswordRequest->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );
    }
}
