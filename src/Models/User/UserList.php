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
            $rowsHtml .= "<tr><td>{$user['nom']}</td><td>{$user['prenom']}</td></tr>";
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
