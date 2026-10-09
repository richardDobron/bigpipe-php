<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class StreamTest extends TestCase
{
    /** @var string[] */
    private array $chunks = [];

    private function stream(): void
    {
        BigPipe::stream(function (string $chunk): void {
            $this->chunks[] = $chunk;
        });
    }

    private function pageletIds(): array
    {
        preg_match_all('/onPageletArrive\(\{"id":"([^"]+)"/', implode('', $this->chunks), $matches);

        return $matches[1];
    }

    public function testFlushesThePageBeforeRenderingTheFirstPagelet(): void
    {
        (new Pagelet('feed'))->defer(function () {
            $this->assertSame([''], $this->chunks);

            return 'posts';
        });

        $this->stream();

        $this->assertStringContainsString('"id":"feed"', $this->chunks[1]);
    }

    public function testSendsEveryPageletBeforeRenderingTheNextOne(): void
    {
        (new Pagelet('header'))->defer(fn () => 'header');
        (new Pagelet('feed'))->defer(function () {
            $this->assertStringContainsString('"id":"header"', end($this->chunks));

            return 'posts';
        });

        $this->stream();

        $this->assertSame(['header', 'feed', BigPipe::LAST_PAGELET_ID], $this->pageletIds());
    }

    public function testSendsPageletsCreatedWhileRenderingAnother(): void
    {
        (new Pagelet('content'))->defer(function () {
            $sidebar = (new Pagelet('sidebar'))->appendContent('friends');

            return "<aside>$sidebar</aside>";
        });

        $this->stream();

        $this->assertSame(['content', 'sidebar', BigPipe::LAST_PAGELET_ID], $this->pageletIds());
    }

    public function testEndsWithTheLastPageletAndTheModulesOfThePage(): void
    {
        (new BigPipe())->require(['Page', 'init']);
        (new Pagelet('feed'))->appendContent('posts');

        $this->stream();

        $last = end($this->chunks);
        $this->assertSame(
            '<script>(new (require("bigpipe-util/dist/BigPipe"))).onPageletArrive({"id":"__bigpipe_last","js":[],"css":[],"domops":[],"jsmods":{"require":[]},"is_last":true});'
            . '(new (require("bigpipe-util/dist/ServerJS"))).handle({"require":[["Page","init"]]});</script>' . "\n",
            $last
        );
        $this->assertStringNotContainsString('is_last', $this->chunks[1]);
    }

    public function testSendsTheDefinesAndTheNonceFirst(): void
    {
        BigPipe::setNonce('r4nd0m');
        (new BigPipe())->define('SiteData', ['locale' => 'sk_SK']);
        (new Pagelet('feed'))->appendContent('posts');

        $this->stream();

        $this->assertSame(
            '<script nonce="r4nd0m">(new (require("bigpipe-util/dist/ServerJS"))).handle({"define":[["CSPNonce","r4nd0m"],["SiteData",{"locale":"sk_SK"}]]});</script>' . "\n",
            $this->chunks[0]
        );
        foreach ($this->chunks as $chunk) {
            $this->assertStringStartsWith('<script nonce="r4nd0m">', $chunk);
        }
        $this->assertStringNotContainsString('"define"', end($this->chunks));
    }

    public function testResetsThePageAfterwards(): void
    {
        (new BigPipe())->require(['Page', 'init']);
        new Pagelet('feed');

        $this->stream();

        $this->assertSame(['require' => []], BigPipe::jsmods());
        $this->assertSame([], BigPipe::context()->pagelets);
    }
}
