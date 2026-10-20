<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use Beaver\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testHtmlResponse(): void
    {
        $r = Response::html('<h1>Olá</h1>');
        $this->assertInstanceOf(Response::class, $r);
    }

    public function testJsonResponse(): void
    {
        $r = Response::json(['ok' => true]);
        $this->assertInstanceOf(Response::class, $r);
    }

    public function testTextResponse(): void
    {
        $r = Response::text('hello');
        $this->assertInstanceOf(Response::class, $r);
    }

    public function testRedirectResponse(): void
    {
        $r = Response::redirect('/login');
        $this->assertInstanceOf(Response::class, $r);
    }

    public function testWithHeader(): void
    {
        $r = Response::html('x')->withHeader('X-Test', 'yes');
        $this->assertInstanceOf(Response::class, $r);
    }
}
