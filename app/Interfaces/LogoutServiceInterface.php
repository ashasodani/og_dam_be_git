<?php

namespace App\Interfaces;

/**
 * Interface LogoutServiceInterface
 *
 * Represents a contract for logout services.
 */
interface LogoutServiceInterface
{
    /**
     * Logout the user.
     *
     * This method should perform actions related to logging out the user from the system.
     * For instance, destroying sessions, clearing authentication tokens, etc.
     *
     * @return void
     */
    public function logout();
}
