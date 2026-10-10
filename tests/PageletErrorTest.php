<?php

declare(strict_types=1);

namespace PageletErrorTest;

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class BrokenPagelet extends Pagelet
{
    protected mixed $fallback = '<p>The feed is not available.</p>';

    protected function content(): string
    {
        $this->call('Feed', 'init');
        echo 'partial';

        throw new \RuntimeException('database is down');
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class PageletErrorTest extends TestCase
{
    /** @var list<array{\Throwable, Pagelet}> */
    private array $reported = [];

    protected function setUp(): void
    {
        BigPipe::setErrorHandler(function (\Throwable $exception, Pagelet $pagelet): void {
            $this->reported[] = [$exception, $pagelet];
        });
    }

    public function testRendersTheFallbackOfAFailingPageletAndTheOtherPagelets(): void
    {
        $broken = new BrokenPagelet();
        (new Pagelet('sidebar'))->appendContent('friends');

        $output = BigPipe::render();

        $this->assertStringContainsString('{"__html":"<p>The feed is not available.<\/p>"}', $output);
        $this->assertStringNotContainsString('partial', $output);
        $this->assertStringNotContainsString('"Feed","init"', $output);
        $this->assertStringContainsString('{"__html":"friends"}', $output);
        $this->assertTrue($broken->hasFailed());
        $this->assertSame('database is down', $this->reported[0][0]->getMessage());
        $this->assertSame($broken, $this->reported[0][1]);
    }

    public function testStreamsThePagesAfterAFailingPagelet(): void
    {
        new BrokenPagelet();
        (new Pagelet('sidebar'))->defer(fn () => 'friends');

        $chunks = [];
        BigPipe::stream(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertStringContainsString('{"__html":"friends"}', implode('', $chunks));
        $this->assertCount(1, $this->reported);
    }

    public function testRendersAFallbackFromACallable(): void
    {
        $pagelet = (new Pagelet('feed'))
            ->defer(function (): void {
                throw new \LogicException('no posts');
            })
            ->setFallback(fn (\Throwable $exception, Pagelet $pagelet) => $pagelet->getId() . ': ' . $exception->getMessage());

        $this->assertSame('feed: no posts', $pagelet->renderContent());
    }

    public function testLogsTheErrorWithoutAHandler(): void
    {
        BigPipe::setErrorHandler(null);
        $log = tempnam(sys_get_temp_dir(), 'bigpipe');
        ini_set('error_log', $log);

        (new Pagelet('feed'))->defer(function (): void {
            throw new \RuntimeException('database is down');
        })->renderContent();

        $this->assertStringContainsString('BigPipe: the pagelet "feed" failed to render: RuntimeException: database is down', file_get_contents($log));
        unlink($log);
    }
}
