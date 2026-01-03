<?php

namespace Views\Base;

use Views\User\ConnectionView;

class ErrorView extends BaseView
{
    public const MESSAGE_KEY = 'MESSAGE_KEY';

    function __construct(
        private \Throwable $exception,
    ) {
        $this->data[self::MESSAGE_KEY] = $this->exception->getMessage();
    }
    function templatePath(): string
    {
        return __DIR__ . '/error.php';
    }

}