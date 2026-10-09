<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class NonceTest extends TestCase
{
    public function testRendersTheScriptWithoutANonceByDefault(): void
    {
        $this->assertStringStartsWith('<script>', BigPipe::render());
        $this->assertSame('', BigPipe::nonceAttribute());
    }

    public function testAddsTheNonceToTheInlineScript(): void
    {
        BigPipe::setNonce('r4nd0m');
        new Pagelet('content');

        $this->assertStringStartsWith('<script nonce="r4nd0m">', BigPipe::render());
        $this->assertSame(' nonce="r4nd0m"', BigPipe::nonceAttribute());
    }

    public function testKeepsTheNonceForTheWholeRequest(): void
    {
        BigPipe::setNonce('r4nd0m');
        BigPipe::render();

        $this->assertStringStartsWith('<script nonce="r4nd0m">', BigPipe::render());
    }

    public function testEscapesTheNonce(): void
    {
        BigPipe::setNonce('"><script>');

        $this->assertSame(' nonce="&quot;&gt;&lt;script&gt;"', BigPipe::nonceAttribute());
    }

    public function testDefinesTheNonceBeforeThePagelets(): void
    {
        BigPipe::setNonce('r4nd0m');
        (new BigPipe())->define('SiteData', []);
        new Pagelet('content');

        $lines = explode("\n", trim(strip_tags(BigPipe::render())));

        $this->assertSame(
            '(new (require("bigpipe-util/dist/ServerJS"))).handle({"define":[["CSPNonce","r4nd0m"],["SiteData",[]]]});',
            trim($lines[0])
        );
        $this->assertStringContainsString('onPageletArrive', $lines[1]);
    }

    public function testDoesNotDefineTheNonceInAsyncResponses(): void
    {
        BigPipe::setNonce('per-request');
        $response = new AsyncResponse();

        $this->assertArrayNotHasKey('define', $response->getResponse()['jsmods']);
    }

    public function testDefinesNoNonceWithoutOne(): void
    {
        $this->assertStringNotContainsString('CSPNonce', BigPipe::render());
    }

    public function testUsesTheNonceOfTheRenderedContext(): void
    {
        $context = new Context();
        $context->nonce = 'own';

        $this->assertStringStartsWith('<script nonce="own">', (string) new BigPipe($context));
    }
}
