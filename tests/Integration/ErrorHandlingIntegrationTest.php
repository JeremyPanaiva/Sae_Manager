<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shared\Exceptions\DataBaseException;

final class ErrorHandlingIntegrationTest extends TestCase
{
    public function testDatabaseExceptionDefaultMessageContainsSupportEmail(): void
    {
        $e = new DataBaseException();

        $this->assertStringContainsString('sae-manager@alwaysdata.net', $e->getMessage());
    }

    public function testDatabaseExceptionCustomMessageIsPreserved(): void
    {
        $msg = 'SMTP server unavailable';
        $e = new DataBaseException($msg);

        $this->assertSame($msg, $e->getMessage());
    }

    public function testExceptionCanBeCaught(): void
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
}