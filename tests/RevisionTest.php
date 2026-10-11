<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use dobron\BigPipe\Quickling;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RevisionTest extends TestCase
{
    public function testThePageDefinesTheRevision(): void
    {
        BigPipe::setRevision('2026.10.1');
        new Pagelet('feed');

        $this->assertStringContainsString('["handle",{"define":[["BigPipeRevision","2026.10.1"]]}]', BigPipe::render());
    }

    public function testEveryResponseCarriesTheRevision(): void
    {
        BigPipe::setRevision('2026.10.1');

        $this->assertSame('2026.10.1', (new AsyncResponse())->getResponse()['revision']);

        $chunks = '';
        (new AsyncResponse())->stream(function (string $part) use (&$chunks): void {
            $chunks .= $part;
        });
        $first = json_decode(explode(AsyncResponse::STREAM_DELIMITER, $chunks)[0], true)['content'];

        $this->assertSame('2026.10.1', $first['revision']);
        $this->assertStringNotContainsString('BigPipeRevision', $chunks);
    }

    public function testSendsNoRevisionWithoutOne(): void
    {
        $this->assertArrayNotHasKey('revision', (new AsyncResponse())->getResponse());
        $this->assertStringNotContainsString('BigPipeRevision', BigPipe::render());
    }

    public function testQuicklingUsesTheRevisionAsTheVersionUnlessItHasItsOwn(): void
    {
        BigPipe::setRevision('2026.10.1');
        $this->assertSame('2026.10.1', Quickling::version());

        Quickling::configure('v2');
        $this->assertSame('v2', Quickling::version());
    }
}
