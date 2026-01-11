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
 * @package Views\Base
 */
abstract class BaseView implements View
{
    /**
     * Current user (null if not authenticated)
     *
     * @var User|null
     */
    protected ? User $user;

    /**
     * Dynamic data for templates
     *
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
        $header = new HeaderView();
        $footer = new FooterView();
        return $header->renderBody()
            . $this->renderBody()
            . $footer->renderBody();
    }

    /**
     * Sets the current user context
     *
     * @param User|null $user The authenticated user or null
     * @return void
     */
    public function setUser(? User $user): void
    {
        $this->user = $user;
    }

    /**
     * Injects data for the template
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Renders the view body from the template
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        $templatePath = $this->templatePath();

        ob_start();
        extract($this->data);
        include $templatePath;
        $output = ob_get_clean();

        return $output !== false ? $output :  '';
    }
}