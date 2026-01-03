<?php

namespace Views;

interface View
{
    function templatePath(): string;

    function renderBody(): string;
}