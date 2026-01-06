<?php

namespace Views\Base;

/**
 * Errors View
 *
 * Renders a list of error messages from multiple exceptions.  Aggregates multiple
 * ErrorView instances into a single unordered list for display.
 *
 * Used to display validation errors, form errors, or any collection of exceptions
 * to the user in a consistent format.
 *
 * @package Views\Base
 */
class ErrorsView extends BaseView
{
    /**
     * Template data key for the errors HTML content
     */
    public const ERRORS_KEY = 'ERRORS_KEY';

    /**
     * Constructor
     *
     * @param \Throwable[] $exceptions Array of exceptions to display as error messages
     */
    function __construct(private array $exceptions)
    {
        $errors = array();
        foreach ($this->exceptions as $exception) {
            $errors[] = (new ErrorView($exception))->renderBody();
        }
        $this->data[self::  ERRORS_KEY] = implode("\n", $errors);
    }

    /**
     * Returns the path to the errors template file
     *
     * @return string Absolute path to the template file
     */
    function templatePath(): string
    {
        return __DIR__ . '/errors.php';
    }
}