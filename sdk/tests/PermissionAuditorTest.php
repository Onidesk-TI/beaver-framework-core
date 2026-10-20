<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\PermissionAuditor;
use PHPUnit\Framework\TestCase;

final class PermissionAuditorTest extends TestCase
{
    public function testRecordGuardaAuditoria(): void
    {
        $auditor = new PermissionAuditor(null);
        $auditor->record('meu-plugin', 'db.read', 'leitura de tabela users');

        $all = $auditor->all();
        $this->assertNotEmpty($all);

        $porPlugin = $auditor->forPlugin('meu-plugin');
        $this->assertNotEmpty($porPlugin);

        $summary = $auditor->summary();
        $this->assertArrayHasKey('meu-plugin', $summary);
        $this->assertArrayHasKey('db.read', $summary['meu-plugin']);
        $this->assertSame(1, $summary['meu-plugin']['db.read']);
    }

    public function testResetLimpa(): void
    {
        $auditor = new PermissionAuditor(null);
        $auditor->record('meu-plugin', 'db.read', 'leitura de tabela users');
        $auditor->reset();

        $this->assertSame([], $auditor->all());
    }
}
