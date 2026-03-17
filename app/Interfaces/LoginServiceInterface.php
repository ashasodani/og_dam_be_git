<?php

namespace App\Interfaces;

/**
 * Interface LoginServiceInterface
 *
 * Represents a contract for login services.
 */
interface LoginServiceInterface
{
    /**
     * Login with the provided credentials.
     *
     * @param array $credentials The user credentials.
     *                          [
     *                              'username' => 'example',
     *                              'password' => 'password123'
     *                          ]
     * @param bool $remember     Flag indicating whether to remember the login or not.
     *
     * @return bool              True if login is successful, false otherwise.
     */
    public function login(array $credentials, bool $remember);

    /**
     * Get Login User data.
     *
     * @return mixed
     */
    public function getUser();
}
