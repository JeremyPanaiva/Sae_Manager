<?php

namespace Views\Base;

use Views\User\ConnectionView;

/**
 * Error View
 *
 * Renders a single error message from an exception.  Displays the exception's
 * message in a styled list item format.
 *
 * Used as a building block for ErrorsView to display individual error messages,
 * or can be used standalone for single error display.
 *
 * @package Views\Base
 */
class ErrorView extends BaseView
{
    /**
     * Template data key for the error message content
     */
    public const MESSAGE_KEY = 'MESSAGE_KEY';

    /**
     * Constructor
     *
     * @param \Throwable $exception The exception whose message will be displayed
     */
    public function __construct(
        private \Throwable $exception,
    ) {
        $this->data[self::MESSAGE_KEY] = $this->exception->getMessage();
    }

    /**
     * Returns the path to the error template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return __DIR__ . '/error.php';
    }
}
