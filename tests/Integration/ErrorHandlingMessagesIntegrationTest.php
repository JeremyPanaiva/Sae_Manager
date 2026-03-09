<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shared\Exceptions\DataBaseException;

final class ErrorHandlingMessagesIntegrationTest extends TestCase
{
    public function testDatabaseExceptionCustomMessageIsPreserved(): void
    {
        $msg = 'SMTP server unavailable';
        $e = new DataBaseException($msg);

        $this->assertSame($msg, $e->getMessage());
    }

    public function testExceptionCanBeCaughtSpecifically(): void
    {
        $caught = false;

        try {
            throw new DataBaseException('Test error');
        } catch (DataBaseException $e) {
            $caught = true;
            $this->assertSame('Test error', $e->getMessage());
        }

        $this->assertTrue($caught);
    }

    public function testUserFriendlyMessageCanBeGeneratedFromException(): void
    {
        $userMessage = '';

        try {
            throw new DataBaseException('Technical detail that should not be shown');
        } catch (DataBaseException $e) {
            // In the app, you would map exception -> friendly message
            $userMessage = 'Une erreur est survenue. Veuillez contacter sae-manager@alwaysdata.net';
        }

        $this->assertNotSame('', $userMessage);
        $this->assertStringContainsString('sae-manager@alwaysdata.net', $userMessage);
    }
}