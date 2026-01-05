<?php

namespace Models\User;

class UserList
{
    private array $users;
    private string $paginationHtml;
    private array $headerData;

    public function __construct(array $users, string $paginationHtml = '', array $headerData = [])
    {
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
        $this->headerData = $headerData;
    }

    public function getRowsHtml(): string
    {
        $rowsHtml = '';
        foreach ($this->users as $user) {
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

    public function getPaginationHtml(): string
    {
        return $this->paginationHtml;
    }

    public function getHeaderData(): array
    {
        return $this->headerData;
    }
}
