<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DeferredPageletTest extends TestCase
{
    public function testRendersTheContentWhenThePageletIsRendered(): void
    {
        $rendered = false;
        $pagelet = (new Pagelet('feed'))
            ->appendContent('<h2>Feed</h2>')
            ->defer(function () use (&$rendered) {
                $rendered = true;

                return '<ul></ul>';
            });

        $this->assertFalse($rendered);
        $this->assertSame('<h2>Feed</h2><ul></ul>', $pagelet->renderContent());
        $this->assertTrue($rendered);
    }

    public function testAppendsWhatTheCallablePrintsAndReturns(): void
    {
        $pagelet = (new Pagelet('feed'))->defer(function () {
            echo '<p>printed</p>';

            return '<p>returned</p>';
        });

        $this->assertSame('<p>printed</p><p>returned</p>', $pagelet->renderContent());
    }

    public function testRendersOnlyOnce(): void
    {
        $calls = 0;
        $pagelet = (new Pagelet('feed'))->defer(function () use (&$calls) {
            $calls++;

            return 'content';
        });

        $pagelet->renderContent();
        $data = $pagelet->renderData();

        $this->assertSame(1, $calls);
        $this->assertSame('content', $data['domops'][0][3]['__html']);
    }

    public function testTheCallableCanAddResourcesAndModules(): void
    {
        $pagelet = (new Pagelet('feed'))->defer(function (Pagelet $pagelet) {
            $pagelet->addCss('/css/feed.css')->require(['Feed', 'init']);

            return 'content';
        });

        $data = $pagelet->renderData();

        $this->assertSame(['/css/feed.css'], $data['css']);
        $this->assertSame([['Feed', 'init']], $data['jsmods']['require']);
    }

    public function testRendersDeferredContentInThePageScript(): void
    {
        (new Pagelet('feed'))->defer(fn () => '<ul></ul>');

        $this->assertStringContainsString('"__html":"<ul><\/ul>"', BigPipe::render());
    }

    public function testCleansTheOutputBufferWhenTheCallableThrows(): void
    {
        $level = ob_get_level();
        BigPipe::setErrorHandler(function (Throwable $exception): void {
            throw $exception;
        });
        $pagelet = (new Pagelet('feed'))->defer(function () {
            echo 'partial';

            throw new RuntimeException('failed');
        });

        try {
            $pagelet->renderContent();
            $this->fail('The exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('failed', $exception->getMessage());
        }

        $this->assertSame($level, ob_get_level());
    }
}
