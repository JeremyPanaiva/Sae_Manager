<?php

namespace Views;

/**
 * Abstract View
 *
 * Base abstract class for views that don't need header/footer.
 * Provides basic template rendering functionality.
 *
 * @package Views
 */
abstract class AbstractView implements View
{
    /**
     * Template data array
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Sets template data
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Renders the view body from template
     *
     * @return string Rendered HTML
     */
    public function renderBody(): string
    {
        $templatePath = $this->templatePath();

        ob_start();
        extract($this->data);
        include $templatePath;
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}
