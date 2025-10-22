<?php

namespace Views;

interface View
{
    function templatePath(): string;

    /**
     * @return array<string, string>
     */
    function templateKeys(): array;

    function renderBody(): string;
}