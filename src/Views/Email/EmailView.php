<?php

namespace Views\Email;

use Views\Base\BaseView;

/**
 * Email View
 *
 * @package Views\Email
 */
abstract class EmailView extends BaseView
{
    /**
     * Template data
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct();
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