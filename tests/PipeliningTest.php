<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PipeliningTest extends TestCase
{
    public function testPipeliningIsOnByDefault(): void
    {
        $this->assertTrue(BigPipe::isPipelining());
        $this->assertSame('<div id="pagelet_feed"></div>', (string) (new Pagelet('feed'))->appendContent('posts'));
    }

    public function testRendersThePageletInItsPlaceholder(): void
    {
        BigPipe::setPipelining(false);

        $feed = (new Pagelet('feed'))
            ->appendContent('<h2>Feed</h2>')
            ->defer(fn () => '<ul></ul>')
            ->addCss('/css/feed.css?v="2"');

        $this->assertSame(
            '<link rel="stylesheet" href="/css/feed.css?v=&quot;2&quot;"><div id="pagelet_feed"><h2>Feed</h2><ul></ul></div>',
            (string) $feed
        );
    }

    public function testLinksTheStylesheetsByTheirUrlInTheResourceMap(): void
    {
        BigPipe::setPipelining(false);
        Bootloader::setResourceMap(['feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css']]);

        $this->assertSame(
            '<link rel="stylesheet" href="/static/feed.1a2b.css"><div id="pagelet_feed"></div>',
            (string) (new Pagelet('feed'))->addCss('feed.css')
        );
    }

    public function testStillSendsTheJavaScriptOfAnInlinePagelet(): void
    {
        BigPipe::setPipelining(false);

        $feed = (new Pagelet('feed'))
            ->appendContent('posts')
            ->addCss('/css/feed.css')
            ->addJs('/js/feed.js')
            ->require(['Feed', 'init']);
        $placeholder = (string) $feed;

        $data = $feed->renderData();

        $this->assertStringContainsString('posts', $placeholder);
        $this->assertSame([], $data['domops']);
        $this->assertSame([], $data['css']);
        $this->assertSame(['/js/feed.js'], $data['js']);
        $this->assertSame([['Feed', 'init']], $data['jsmods']['require']);
    }

    public function testRendersNestedPageletsInline(): void
    {
        BigPipe::setPipelining(false);

        $content = (new Pagelet('content'))->defer(function () {
            $sidebar = (new Pagelet('sidebar'))->appendContent('friends');

            return "<aside>$sidebar</aside>";
        });

        $this->assertSame('<div id="pagelet_content"><aside><div id="pagelet_sidebar">friends</div></aside></div>', (string) $content);
        $this->assertStringNotContainsString('setContent', BigPipe::render());
    }

    public function testFillsAPageletThatWasNotPrintedInTheBrowser(): void
    {
        BigPipe::setPipelining(false);
        (new Pagelet('feed'))->appendContent('posts');

        $this->assertStringContainsString('["setContent","#pagelet_feed",false,{"__html":"posts"}]', BigPipe::render());
    }

    public function testKeepsTheSettingForTheWholeRequest(): void
    {
        BigPipe::setPipelining(false);
        BigPipe::render();

        $this->assertFalse(BigPipe::isPipelining());
    }
}
