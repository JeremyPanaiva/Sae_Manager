<?php

namespace Views\Email;

use Views\Base\BaseView;

/**
 * Email View
 *
 * Renders email templates with dynamic data injection.
 * Used for generating HTML email content for password reset, notifications, etc.
 *
 * Unlike standard views, this class:
 * - Does not include header/footer (emails are standalone)
 * - Overrides render() to skip BaseView's header/footer wrapping
 * - Uses dynamic template selection based on email type
 *
 * @package Views\Email
 */
class EmailView extends BaseView
{
    /**
     * Template filename (without . php extension)
     *
     * @var string
     */
    private string $templateName;

    /**
     * Template data for variable injection
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param string $templateName Template filename without extension (e.g., 'password_reset')
     * @param array<string, mixed> $data Associative array of variables to inject into the template
     */
    public function __construct(string $templateName, array $data = [])
    {
        $this->templateName = $templateName;
        $this->data = $data;
    }

    /**
     * Returns the path to the email template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return __DIR__ . '/' . $this->templateName . '.php';
    }

    /**
     * Returns template variables for injection
     *
     * @return array<string, mixed> Template data array
     */
    protected function templateVariables(): array
    {
        return $this->data;
    }

    /**
     * Renders the email template with variables
     *
     * Overrides BaseView:: render() to skip header/footer inclusion.
     * Loads the template file, extracts variables, and captures output.
     *
     * @return string Rendered HTML email content
     * @throws \Exception If the template file does not exist
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
        return (string) ob_get_clean();
    }
}
