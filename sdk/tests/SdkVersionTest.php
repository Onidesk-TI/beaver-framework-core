<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\SdkVersion;
use PHPUnit\Framework\TestCase;

final class SdkVersionTest extends TestCase
{
    public function testApiVersion(): void
    {
        $this->assertSame('1.0.0', SdkVersion::API_VERSION);
    }

    public function testSatisfies(): void
    {
        $this->assertTrue(SdkVersion::satisfies('1.2.0', '>=1.0.0'));
        $this->assertTrue(SdkVersion::satisfies('1.5.0', '^1.0.0'));
        $this->assertFalse(SdkVersion::satisfies('0.9.0', '>=1.0.0'));
    }
}
