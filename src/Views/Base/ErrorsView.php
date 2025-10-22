<?php

namespace Views\Base;

class ErrorsView  extends BaseView
{
    public CONST ERRORS_KEY = 'ERRORS_KEY';

    /**
     * @param \Throwable[]  $exceptions
     */
    function __construct(private array $exceptions) {

    }
    function templatePath(): string
    {
        return __DIR__ . '/errors.html';    }

    function templateKeys(): array
    {
        $errors = array();
        foreach ($this->exceptions as $exception) {
            $errors[] = (new ErrorView($exception))->renderBody();
        }
        return [
            self::ERRORS_KEY=>implode("\n", $errors),
        ];
    }
}