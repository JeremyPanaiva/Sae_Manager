<?php

namespace Models\User;

/**
 * User List model
 *
 * Encapsulates user list data for display purposes.   Generates HTML table rows
 * for user data and manages pagination controls.  Used primarily by the user
 * list view to separate data processing from presentation logic.
 *
 * @package Models\User
 */
class UserList
{
    /**
     * Array of user data
     *
     * @var array
     */
    private array $users;

    /**
     * Pre-generated pagination HTML
     *
     * @var string
     */
    private string $paginationHtml;

    /**
     * Additional header data for the view
     *
     * @var array
     */
    private array $headerData;

    /**
     * Constructor
     *
     * @param array $users Array of user data (each user as associative array)
     * @param string $paginationHtml Pre-generated pagination HTML controls
     * @param array $headerData Additional data for page header
     */
    public function __construct(array $users, string $paginationHtml = '', array $headerData = [])
    {
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
        $this->headerData = $headerData;
    }

    /**
     * Generates HTML table rows for the user list
     *
     * Creates table rows with user information including first name, last name,
     * email, and role.  All output is properly escaped to prevent XSS attacks.
     *
     * @return string HTML string containing table rows
     */
    public function getRowsHtml(): string
    {
        $rowsHtml = '';
        foreach ($this->users as $user) {
            // Escape all output to prevent XSS
            $prenom = htmlspecialchars($user['prenom'] ?? '');
            $nom = htmlspecialchars($user['nom'] ?? '');
            $mail = htmlspecialchars($user['mail'] ?? '');
            $role = htmlspecialchars(ucfirst($user['role'] ?? ''));

            $rowsHtml .= "<tr>";
            $rowsHtml .= "<td>{$prenom}</td>";
            $rowsHtml .= "<td>{$nom}</td>";
            $rowsHtml .= "<td>{$mail}</td>";
            $rowsHtml .= "<td>{$role}</td>";
            $rowsHtml .= "</tr>";
        }
        return $rowsHtml;
    }

    /**
     * Gets the pagination HTML
     *
     * @return string Pre-generated pagination controls HTML
     */
    public function getPaginationHtml(): string
    {
        return $this->paginationHtml;
    }

    /**
     * Gets the header data
     *
     * @return array Additional data for the page header
     */
    public function getHeaderData(): array
    {
        return $this->headerData;
    }
}