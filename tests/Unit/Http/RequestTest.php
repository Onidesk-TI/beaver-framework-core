<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use Beaver\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private function makeRequest(array $overrides = []): Request
    {
        return new Request(
            method:  $overrides['method']  ?? 'GET',
            uri:     $overrides['uri']     ?? '/',
            query:   $overrides['query']   ?? [],
            body:    $overrides['body']    ?? [],
            headers: $overrides['headers'] ?? [],
            server:  $overrides['server']  ?? [],
            files:   $overrides['files']   ?? [],
            cookies: $overrides['cookies'] ?? [],
        );
    }

    public function testMethodAndUri(): void
    {
        $r = $this->makeRequest(['method' => 'POST', 'uri' => '/contacto']);
        $this->assertSame('POST', $r->method);
        $this->assertSame('/contacto', $r->uri);
    }

    public function testIsMethod(): void
    {
        $r = $this->makeRequest(['method' => 'POST']);
        $this->assertTrue($r->isMethod('POST'));
        $this->assertTrue($r->isMethod('post'));
        $this->assertFalse($r->isMethod('GET'));
    }

    public function testInputPriorizaBodySobreQuery(): void
    {
        $r = $this->makeRequest([
            'query' => ['nome' => 'query'],
            'body'  => ['nome' => 'body'],
        ]);
        $this->assertSame('body', $r->input('nome'));
    }

    public function testInputDevolveDefaultSeNaoExiste(): void
    {
        $r = $this->makeRequest();
        $this->assertSame('fallback', $r->input('inexistente', 'fallback'));
    }

    public function testAllFundeQueryEBody(): void
    {
        $r = $this->makeRequest([
            'query' => ['a' => 1],
            'body'  => ['b' => 2],
        ]);
        $all = $r->all();
        $this->assertSame(1, $all['a']);
        $this->assertSame(2, $all['b']);
    }

    public function testHeaderNormalizaNome(): void
    {
        $r = $this->makeRequest([
            'headers' => ['CONTENT_TYPE' => 'application/json'],
        ]);
        $this->assertSame('application/json', $r->header('Content-Type'));
        $this->assertSame('application/json', $r->header('content-type'));
        $this->assertSame('application/json', $r->header('CONTENT_TYPE'));
    }

    public function testHeaderDefault(): void
    {
        $r = $this->makeRequest();
        $this->assertSame('x', $r->header('X-Inexistente', 'x'));
    }
}
