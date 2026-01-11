<?php

namespace Views\Email;

use Views\Base\BaseView;

/**
 * Email View
 *
 * @package Views\Email
 */
class EmailView extends BaseView
{
    /**
     * Template data
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * @var string
     */
    private string $templateName;

    /**
     * Constructor
     *
     * @param string $templateName
     * @param array<string, mixed> $data
     */
    public function __construct(string $templateName, array $data = [])
    {
        parent::__construct();
        $this->templateName = $templateName;
        $this->data = $data;
    }

    /**
     * Template variables
     *
     * @return array<string, mixed>
     */
    protected function templateVariables(): array
    {
        return $this->data;
    }

    /**
     * Returns template path
     *
     * @return string
     */
    public function templatePath(): string
    {
        return __DIR__ . '/' . $this->templateName . '.php';
    }

    /**
     * Renders email HTML
     *
     * @return string
     */
    public function render(): string
    {
        ob_start();
        extract($this->data);
        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}
