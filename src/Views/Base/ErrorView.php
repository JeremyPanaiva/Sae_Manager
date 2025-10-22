<?php

namespace Views\Base;

use Views\User\ConnectionView;

class ErrorView extends BaseView
{
    public CONST MESSAGE_KEY = 'MESSAGE_KEY';

    function __construct(
        private \Throwable $exception,
    ) {

    }
    function templatePath(): string
    {
        return __DIR__ . '/error.html';    }

    function templateKeys(): array
    {
       return [
           self::MESSAGE_KEY=>$this->exception->getMessage()
       ];
    }
}