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
class ContextTest extends TestCase
{
    public function testInstancesShareTheCurrentContext(): void
    {
        (new BigPipe())->require("require('first')");

        $response = new AsyncResponse();
        $response->bigPipe()->require("require('second')");

        $this->assertSame([['first'], ['second']], $response->getResponse()['jsmods']['require']);
    }

    public function testWithContextIsolatesAndRestores(): void
    {
        (new BigPipe())->require("require('outer')");

        $inner = BigPipe::withContext(function (Context $context) {
            (new BigPipe())->require("require('inner')");
            new Pagelet('inner');

            $this->assertSame($context, BigPipe::context());
            $this->assertCount(1, $context->pagelets);

            return BigPipe::jsmods()['require'];
        });

        $this->assertSame([['inner']], $inner);
        $this->assertSame([['outer']], BigPipe::jsmods()['require']);
        $this->assertSame([], BigPipe::context()->pagelets);
    }

    public function testWithContextRestoresOnException(): void
    {
        $outer = BigPipe::context();

        try {
            BigPipe::withContext(function () {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame($outer, BigPipe::context());
    }

    public function testContextResolver(): void
    {
        $contexts = ['a' => new Context(), 'b' => new Context()];
        $current = 'a';

        BigPipe::setContextResolver(function () use (&$contexts, &$current) {
            return $contexts[$current];
        });

        (new BigPipe())->require("require('requestA')");
        $current = 'b';
        (new BigPipe())->require("require('requestB')");

        $this->assertSame([['requestA']], $contexts['a']->jsmods()['require']);
        $this->assertSame([['requestB']], $contexts['b']->jsmods()['require']);

        BigPipe::setContextResolver(null);
        $this->assertSame([], BigPipe::jsmods()['require']);
    }

    public function testResponseKeepsItsOwnContext(): void
    {
        $context = new Context();
        $response = new AsyncResponse($context);
        $response->bigPipe()->require("require('mine')");

        $this->assertSame([], BigPipe::jsmods()['require']);
        $this->assertSame([['mine']], $response->getResponse()['jsmods']['require']);
    }

    public function testFailedResponseDoesNotLeakIntoNextRequest(): void
    {
        $response = new AsyncResponse();
        $response->bigPipe()->require("require('leaked')");
        $response->setPayload(["\xB1\x31"]);

        try {
            $response->buildResponseString();
            $this->fail('Expected a JSON exception.');
        } catch (JsonException) {
        }

        $this->assertSame([], BigPipe::jsmods()['require']);
    }

    public function testFailedRenderDoesNotLeakIntoNextRequest(): void
    {
        (new Pagelet('broken'))->appendContent("\xB1\x31");

        try {
            BigPipe::render();
            $this->fail('Expected a JSON exception.');
        } catch (JsonException) {
        }

        $this->assertSame([], BigPipe::context()->pagelets);
    }
}
