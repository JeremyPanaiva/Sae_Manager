<?php

namespace Models\User;

/**
 * User Data Transfer Object (DTO)
 *
 * A simple data container for transferring user credentials between layers
 * of the application.  Encapsulates username and password data.
 *
 * Note: This class appears to be legacy code and may not be actively used
 * in the current application architecture, as user authentication is handled
 * directly through the User model with email-based authentication.
 *
 * @package Models\User
 */
class UserDTO
{
    /**
     * Username
     *
     * @var string
     */
    private string $username;

    /**
     * Password
     *
     * @var string
     */
    private string $password;

    /**
     * Constructor
     *
     * @param string $username The username
     * @param string $password The password (plain text or hashed)
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Gets the username
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Gets the password
     *
     * @return string The password
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
