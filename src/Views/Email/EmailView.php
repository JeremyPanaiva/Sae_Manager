<?php

namespace Views\Email;

use Views\Base\BaseView;

class EmailView extends BaseView
{
    private string $templateName;
    protected array $data;

    /**
     * @param string $templateName - nom du template (sans .html)
     * @param array $data - données à injecter dans le template
     */
    public function __construct(string $templateName, array $data = [])
    {
        $this->templateName = $templateName;
        $this->data = $data;
    }

    public function templatePath(): string
    {
        return __DIR__ . '/' . $this->templateName . '.php';
    }

    protected function templateVariables(): array
    {
        return $this->data;
    }

    /**
     * Render le template email avec les variables
     */
    public function render(): string
    {
        $templatePath = $this->templatePath();

        if (!file_exists($templatePath)) {
            error_log("Template email non trouvé : {$templatePath}");
            throw new \Exception("Template email non trouvé : {$this->templateName}");
        }

        ob_start();
        extract($this->data);
        include $templatePath;
        return ob_get_clean();
    }
}