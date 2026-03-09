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
    /** @var string */
    private string $templatePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->templatePath = __DIR__ . '/../../src/Views/Email/';

        if (!is_dir($this->templatePath)) {
            $this->markTestSkipped('Email templates directory does not exist');
        }
    }

    /**
     * @param string $templateFile
     * @param array<string, string> $variables
     * @return string
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

        $this->assertStringContainsString('<!DOCTYPE', $rendered);
        $this->assertStringContainsString('<html', $rendered);
        $this->assertStringContainsString('</html>', $rendered);
        $this->assertStringContainsString('<body', $rendered);
        $this->assertStringContainsString('</body>', $rendered);
    }

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

        $this->assertStringContainsString($testUrl, $rendered);
        $this->assertStringContainsString('href=', $rendered);
    }

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

        $this->assertStringContainsString('style=', $rendered);
    }

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

        $this->assertMatchesRegularExpression(
            '/charset=["\']?utf-8["\']?/i',
            $rendered,
            'Template should declare UTF-8 charset'
        );
    }
}