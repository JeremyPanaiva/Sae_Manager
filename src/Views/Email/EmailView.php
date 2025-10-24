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
        return __DIR__ . '/' . $this->templateName . '.html';
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

        $template = file_get_contents($templatePath);

        // Remplacer les variables {{KEY}} par leurs valeurs
        foreach ($this->data as $key => $value) {
            // Sécuriser les valeurs pour éviter les injections XSS
            $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $template = str_replace('{{' . $key . '}}', $safeValue, $template);
        }

        return $template;
    }

    function templateKeys(): array
    {
        // TODO: Implement templateKeys() method.
    }
}