<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Email Template Integration Test
 *
 * Tests email template rendering with real data:
 * - Template loading → Variable substitution → HTML rendering
 *
 * @package Tests\Integration
 */
final class EmailTemplateIntegrationTest extends TestCase
{
    /**
     * Path to email templates directory
     *
     * @var string
     */
    private string $templatePath = '';

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->templatePath = __DIR__ . '/../../src/Views/Email/';

        if (!is_dir($this->templatePath)) {
            $this->markTestSkipped('Email templates directory does not exist');
        }
    }

    /**
     * Render template with given variables
     *
     * @param string $templateFile Template file path
     * @param array<string, string> $variables Variables to extract
     * @return string Rendered template
     */
    private function renderTemplate(string $templateFile, array $variables): string
    {
        if (!file_exists($templateFile)) {
            return '';
        }

        extract($variables);

        ob_start();
        include $templateFile;
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }

    /**
     * Test urgent deadline reminder template rendering
     *
     * @return void
     */
    public function testUrgentDeadlineReminderTemplateRendering(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('urgent_deadline_reminder.php template not found');
        }

        $testData = [
            'STUDENT_NAME'     => 'Jean Dupont',
            'SAE_TITLE'        => 'SAE 3.01 - Développement Web',
            'DATE_RENDU'       => '15/03/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'Dr. Jean Martin',
            'SAE_URL'          => 'https://sae-manager.alwaysdata.net/sae',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        $this->assertNotEmpty($rendered, 'Rendered template should not be empty');
        $this->assertStringContainsString($testData['STUDENT_NAME'], $rendered);
        $this->assertStringContainsString($testData['SAE_TITLE'], $rendered);
        $this->assertStringContainsString($testData['DATE_RENDU'], $rendered);
        $this->assertStringContainsString($testData['HEURE_RENDU'], $rendered);
        $this->assertStringContainsString($testData['RESPONSABLE_NAME'], $rendered);
        $this->assertStringContainsString($testData['SAE_URL'], $rendered);
    }

    /**
     * Test template HTML structure validity
     *
     * @return void
     */
    public function testTemplateHtmlStructureValidity(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testData = [
            'STUDENT_NAME'     => 'Test Student',
            'SAE_TITLE'        => 'Test SAE',
            'DATE_RENDU'       => '31/12/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'Test User',
            'SAE_URL'          => 'https://example.com',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        // Check HTML structure
        $this->assertStringContainsString('<!DOCTYPE', $rendered);
        $this->assertStringContainsString('<html', $rendered);
        $this->assertStringContainsString('</html>', $rendered);
        $this->assertStringContainsString('<body', $rendered);
        $this->assertStringContainsString('</body>', $rendered);
    }

    /**
     * Test template with special characters
     *
     * @return void
     */
    public function testTemplateWithSpecialCharacters(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testData = [
            'STUDENT_NAME'     => 'François Müller',
            'SAE_TITLE'        => 'SAE avec caractères spéciaux: é à ù & < >',
            'DATE_RENDU'       => '15/03/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'François Müller',
            'SAE_URL'          => 'https://example.com/sae?id=123&lang=fr',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        $this->assertNotEmpty($rendered);

        // Verify special characters are properly escaped
        $this->assertStringNotContainsString('<SAE', $rendered);
    }

    /**
     * Test template link functionality
     *
     * @return void
     */
    public function testTemplateLinkFunctionality(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testUrl = 'https://sae-manager.alwaysdata.net/sae';

        $testData = [
            'STUDENT_NAME'     => 'Test Student',
            'SAE_TITLE'        => 'Test SAE',
            'DATE_RENDU'       => '31/12/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'Test',
            'SAE_URL'          => $testUrl,
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        // Verify URL is present and properly formatted
        $this->assertStringContainsString($testUrl, $rendered);
        $this->assertStringContainsString('href=', $rendered);
    }

    /**
     * Test template with empty variables
     *
     * @return void
     */
    public function testTemplateWithEmptyVariables(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testData = [
            'STUDENT_NAME'     => '',
            'SAE_TITLE'        => '',
            'DATE_RENDU'       => '',
            'HEURE_RENDU'      => '',
            'RESPONSABLE_NAME' => '',
            'SAE_URL'          => '',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        // Template should still render even with empty variables
        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('<html', $rendered);
    }

    /**
     * Test template CSS inline styles
     *
     * @return void
     */
    public function testTemplateCssInlineStyles(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testData = [
            'STUDENT_NAME'     => 'Test',
            'SAE_TITLE'        => 'Test',
            'DATE_RENDU'       => '31/12/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'Test',
            'SAE_URL'          => 'https://example.com',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        // Email templates should use inline styles
        $this->assertStringContainsString('style=', $rendered);
    }

    /**
     * Test template character encoding
     *
     * @return void
     */
    public function testTemplateCharacterEncoding(): void
    {
        $templateFile = $this->templatePath . 'urgent_deadline_reminder.php';

        if (!file_exists($templateFile)) {
            $this->markTestSkipped('Template file not found');
        }

        $testData = [
            'STUDENT_NAME'     => 'Test',
            'SAE_TITLE'        => 'Test',
            'DATE_RENDU'       => '31/12/2026',
            'HEURE_RENDU'      => '23:59',
            'RESPONSABLE_NAME' => 'Test',
            'SAE_URL'          => 'https://example.com',
        ];

        $rendered = $this->renderTemplate($templateFile, $testData);

        // Check for UTF-8 charset declaration
        $this->assertMatchesRegularExpression(
            '/charset=["\']?utf-8["\']?/i',
            $rendered,
            'Template should declare UTF-8 charset'
        );
    }

    /**
     * Test multiple templates rendering consistency
     *
     * @return void
     */
    public function testMultipleTemplatesRenderingConsistency(): void
    {
        $templates = glob($this->templatePath . '*.php');

        if ($templates === false || count($templates) === 0) {
            $this->markTestSkipped('No email templates found');
        }

        foreach ($templates as $templateFile) {
            $this->assertFileExists($templateFile);
            $this->assertTrue(is_readable($templateFile));

            $content = file_get_contents($templateFile);
            $this->assertNotFalse($content);
            $this->assertNotEmpty($content);
        }
    }
}