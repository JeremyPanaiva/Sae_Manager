<?php

namespace Views;

/**
 * Abstract View
 *
 * Base abstract class for all view components in the application.
 * Provides common functionality for template rendering with dynamic data injection.
 *
 * This class uses PHP templates with output buffering and variable extraction
 * to render HTML content.  Concrete view classes must implement the View interface
 * and provide their specific template path.
 *
 * Template System:
 * - Data is stored in the $data array as key-value pairs
 * - Keys are extracted as variables in template scope via extract()
 * - Templates are pure PHP files that can access extracted variables
 * - Output is captured using output buffering (ob_start/ob_get_clean)
 *
 * @package Views
 */
abstract class AbstractView implements View
{
    /**
     * Dynamic data provided to templates (key => value)
     *
     * Example: ['token' => 'abc', 'email' => 'a@b.c']
     * Keys become variables in the template scope after extraction.
     *
     * @var array<string,string>
     */
    protected array $data = [];

    /**
     * Injects data for the template
     *
     * Merges provided data with existing data.  Allows adding or overriding
     * template variables without replacing the entire data array.
     *
     * @param array<string,string> $data Associative array of template variables
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Renders the view body from the template
     *
     * Loads the template file specified by templatePath(), extracts data variables
     * into the current scope, and captures the output using output buffering.
     *
     * Template files can access any key from $this->data as a variable.
     * For example, if $data = ['username' => 'John'], the template can use $username.
     *
     * @return string Rendered HTML body content
     */
    function renderBody(): string
    {
        $templatePath = $this->templatePath();

        ob_start();
        extract($this->data);
        include $templatePath;
        return ob_get_clean();
    }
}