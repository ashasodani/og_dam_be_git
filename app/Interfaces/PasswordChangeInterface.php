<?php

namespace App\Interfaces;

/**
 * Interface PasswordChangeInterface
 *
 * Represents a contract for password change services.
 */
interface PasswordChangeInterface
{
    /**
     * Change the user's password.
     *
     * This method should handle the logic for changing the user's password based on the provided request.
     *
     * @param mixed $request The request containing information needed for changing the password.
     *                      This could be an array, an object, or any appropriate data structure.
     *                      [
     *                          'old_password' => 'currentPassword',
     *                          'new_password' => 'newPassword123'
     *                      ]
     *
     * @return mixed The result of the password change operation.
     *               This could be a boolean indicating success/failure, a status code, or other appropriate values.
     */
    public function changePassword($request);
}
