<?php

namespace Views\Base;

use Models\User\User;
use Views\View;

/**
 * Base View
 *
 * Abstract base class for all application views.  Provides common functionality
 * for rendering views with header and footer, user context, and template data
 * injection.
 *
 * All concrete views should extend this class and implement the templatePath()
 * method to specify their template file location.
 *
 * Template System:
 * - Uses PHP templates with output buffering
 * - Data is extracted into template scope via extract()
 * - Templates can access $data array keys as variables
 *
 * @package Views\Base
 */
abstract class BaseView implements View
{
    /**
     * Header view instance
     *
     * @var HeaderView
     */
    private HeaderView $header;

    /**
     * Footer view instance
     *
     * @var FooterView
     */
    private FooterView $footer;

    /**
     * Current user (null if not authenticated)
     *
     * @var User|null
     */
    protected ?User $user;

    /**
     * Données dynamiques fournies aux templates (clé => valeur)
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user = null;
    }

    /**
     * Renders the complete view (header + body + footer)
     *
     * @return string Complete HTML output
     */
    public function render(): string
    {
        $this->header = new HeaderView();
        $this->footer = new FooterView();
        return $this->header->renderBody()
            . $this->renderBody()
            . $this->footer->renderBody();
    }

    /**
     * Sets the current user context
     *
     * @param User|null $user The authenticated user or null
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * Injecte des données pour le template
     * @param array<string,string> $data
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Safely converts a mixed value to a string.
     * Returns the string representation if scalar or Stringable, empty string otherwise.
     *
     * @param mixed $value
     * @return string
     */
    protected function safeString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }
        return '';
    }

    /**
     * Renders the view body from the template
     *
     * Loads the template file specified by templatePath(), extracts data variables
     * into the template scope, and captures the output using output buffering.
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        $templatePath = $this->templatePath();

        ob_start();
        extract($this->data);
        include $templatePath;
        return (string) ob_get_clean();
    }
}
