<?php

namespace Views;

/**
 * View Interface
 *
 * Defines the contract for all view components in the application.
 * Views are responsible for rendering HTML content from templates.
 *
 * Implementing classes must provide:
 * - A template file path
 * - A method to render the template content
 *
 * This interface enables consistent view rendering across the application
 * and allows for different view implementations (e.g., with or without layouts).
 *
 * @package Views
 */
interface View
{
    /**
     * Returns the path to the template file
     *
     * Must return an absolute path to a PHP template file that will be
     * included during rendering.
     *
     * @return string Absolute path to the template file
     */
    function templatePath(): string;

    /**
     * Renders the view body content
     *
     * Processes the template file and returns the rendered HTML output.
     * This method typically uses output buffering to capture template output.
     *
     * @return string Rendered HTML body content
     */
    function renderBody(): string;
}