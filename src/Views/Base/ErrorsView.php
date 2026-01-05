<?php

namespace Views\Base;

class ErrorsView extends BaseView
{
    public const ERRORS_KEY = 'ERRORS_KEY';

    /**
     * @param \Throwable[]  $exceptions
     */
    function __construct(private array $exceptions)
    {
        $errors = array();
        foreach ($this->exceptions as $exception) {
            $errors[] = (new ErrorView($exception))->renderBody();
        }
        $this->data[self::ERRORS_KEY] = implode("\n", $errors);
    }
    function templatePath(): string
    {
        return __DIR__ . '/errors.php';
    }

}