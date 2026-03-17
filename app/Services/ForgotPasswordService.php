<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Class AdminForgotPasswordService
 *
 * This class provides services for sending reset links for forgotten passwords for admin users.
 *
 * @package App\Services
 */
class ForgotPasswordService
{
    /**
     * Send a password reset link to the specified email.
     *
     *
     * @return bool
     */
    public function sendResetLink($data): ?array
    {
        $broker = 'users';

        $user = \App\Models\User::where('email', $data['email'])->first();

        if (! $user) {
            return null; // User not found
        }

        // MANUALLY create token
        $token = Password::broker($broker)->createToken($user);

        // Send the email manually (OR skip if frontend will send)
        $user->sendPasswordResetNotification($token);

        // Return the user email, token, reset link
        return [
            'email' => $user->email,
            'token' => $token,
        ];
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
        $broker = 'users';
        $user   = null;

        $status = Password::broker($broker)->reset(
            $resetPasswordRequest->only('email', 'password', 'password_confirmation', 'token'),
            function ($foundUser, $password) use (&$user) {
                $foundUser->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));
                $foundUser->save();
                $user = $foundUser; // capture user here
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return $user; // return user object
        }

        return null; // failed
    }

    /**
     * Change the password for the authenticated admin user.
     *
     * @param array $request
     * @return bool
     */
    // public function changePassword($request)
    // {
    //     $userId = Auth::guard(AdminEnum::GuardName->value)->user()->id;
    //     $data   = ['password' => $request['new_password']];
    //     $this->adminUserRepository->update($userId, $data);
    //     return true;
    // }
}
