<?php

declare(strict_types=1);

namespace PageletClassTest;

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\LazyPagelet;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class FeedPagelet extends Pagelet
{
    public static int $renders = 0;

    protected array $css = ['/css/feed.css'];
    protected array $js = ['/js/feed.js'];

    protected function content(): string
    {
        static::$renders++;
        $this->require(['Feed', 'init']);

        return '<ul class="posts"></ul>';
    }
}

class UserProfilePagelet extends Pagelet
{
    protected function content(): void
    {
        echo '<h2>Profile</h2>';
    }
}

class SidebarPagelet extends Pagelet
{
    protected string $id = 'right_column';
}

/**
 * @runTestsInSeparateProcesses
 */
class PageletClassTest extends TestCase
{
    public function testRendersItsContentWhenItIsRendered(): void
    {
        $feed = new FeedPagelet();
        $this->assertSame(0, FeedPagelet::$renders);

        $data = $feed->renderData();
        $feed->renderData();

        $this->assertSame(1, FeedPagelet::$renders);
        $this->assertSame('<ul class="posts"></ul>', $data['domops'][0][3]['__html']);
        $this->assertSame(['/css/feed.css'], $data['css']);
        $this->assertSame(['/js/feed.js'], $data['js']);
        $this->assertSame([['Feed', 'init']], $data['jsmods']['require']);
    }

    public function testAppendsWhatContentPrints(): void
    {
        $this->assertSame('<h2>Profile</h2>', (new UserProfilePagelet())->renderContent());
    }

    public function testGetsItsIdFromTheClassName(): void
    {
        $this->assertSame('feed', (new FeedPagelet())->getId());
        $this->assertSame('user_profile', (new UserProfilePagelet())->getId());
        $this->assertSame('right_column', (new SidebarPagelet())->getId());
        $this->assertSame('main_feed', (new FeedPagelet('main_feed'))->getId());
    }

    public function testIsRenderedWithThePage(): void
    {
        echo new FeedPagelet();

        $this->assertStringContainsString('"id":"feed"', BigPipe::render());
        $this->expectOutputString('<div id="pagelet_feed"></div>');
    }

    public function testContentComesAfterContentAppendedRightAway(): void
    {
        $feed = (new FeedPagelet())->appendContent('<h2>Feed</h2>');

        $this->assertSame('<h2>Feed</h2><ul class="posts"></ul>', $feed->renderContent());
    }

    public function testReturnsALazyPlaceholder(): void
    {
        $placeholder = FeedPagelet::lazy('/pagelets/feed', [], '<p>Loading…</p>', LazyPagelet::LOAD_IDLE);

        $this->assertInstanceOf(LazyPagelet::class, $placeholder);
        $this->assertSame('<div id="pagelet_feed"><p>Loading…</p></div>', (string) $placeholder);
        $this->assertSame([], BigPipe::context()->pagelets);
    }

    public function testAPageletWithoutAClassNeedsAnId(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        new Pagelet();
    }

    public function testIsRenderedInAnElementNamedAfterItsId(): void
    {
        $this->assertSame('pagelet_feed', Pagelet::rootId('feed'));
        $this->assertSame('<div id="pagelet_main-feed_2"></div>', (string) new Pagelet('main-feed_2'));
    }

    /**
     * @dataProvider invalidIds
     */
    public function testRejectsIdsThatCanNotBeAnElementId(string $id): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        new Pagelet($id);
    }

    public static function invalidIds(): array
    {
        return [['say "hi"'], ['2column'], ['feed.posts'], ['']];
    }
}
