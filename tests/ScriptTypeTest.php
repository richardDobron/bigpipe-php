<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ScriptTypeTest extends TestCase
{
    public function testRendersClassicScriptsByDefault(): void
    {
        $this->assertStringStartsWith('<script>', BigPipe::render());
    }

    public function testRendersModuleScripts(): void
    {
        BigPipe::setScriptType('module');

        $this->assertStringStartsWith('<script type="module">', BigPipe::render());
    }

    public function testPutsTheTypeBeforeTheNonce(): void
    {
        BigPipe::setScriptType('module');
        BigPipe::setNonce('r4nd0m');

        $this->assertStringStartsWith('<script type="module" nonce="r4nd0m">', BigPipe::render());
    }

    public function testStreamsModuleScripts(): void
    {
        BigPipe::setScriptType('module');
        (new Pagelet('feed'))->defer(fn () => 'posts');
        $chunks = [];

        BigPipe::stream(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertStringStartsWith('<script type="module">', $chunks[1]);
        $this->assertStringStartsWith('<script type="module">', end($chunks));
    }

    public function testCanBeSwitchedBack(): void
    {
        BigPipe::setScriptType('module');
        BigPipe::setScriptType(null);

        $this->assertStringStartsWith('<script>', BigPipe::render());
    }

    public function testRejectsOtherTypes(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        BigPipe::setScriptType('text/javascript');
    }
}
