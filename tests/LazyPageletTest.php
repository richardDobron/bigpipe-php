<?php

declare(strict_types=1);

namespace LazyPageletTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\LazyPagelet;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class FeedPagelet extends Pagelet
{
    protected function content(): string
    {
        return '<h2>Feed</h2>' . CommentsPagelet::lazy('/pagelets/comments', ['post' => 7]);
    }
}

class CommentsPagelet extends Pagelet
{
    protected string $id = 'post_comments';
}

/**
 * @runTestsInSeparateProcesses
 */
class LazyPageletTest extends TestCase
{
    private function loadCall(string $url, string $rootId, array $data = [], string $load = 'visible'): array
    {
        return [LazyPagelet::MODULE, 'loadFromEndpoint', [$url, $rootId, $data, ['load' => $load]]];
    }

    public function testPrintsTheRootElementAndCallsUIPagelet(): void
    {
        $placeholder = new LazyPagelet('feed', '/pagelets/feed', [], '<p>Loading…</p>');

        $this->assertSame('<div id="pagelet_feed"><p>Loading…</p></div>', (string) $placeholder);
        $this->assertSame([$this->loadCall('/pagelets/feed', 'pagelet_feed')], BigPipe::jsmods()['require']);
    }

    public function testSendsTheDataAndWhenToLoad(): void
    {
        (string) (new LazyPagelet('stats', '/pagelets/stats'))
            ->setData(['period' => 'week'])
            ->setLoad(LazyPagelet::LOAD_IDLE);

        $this->assertSame(
            [$this->loadCall('/pagelets/stats', 'pagelet_stats', ['period' => 'week'], 'idle')],
            BigPipe::jsmods()['require']
        );
    }

    public function testCallsUIPageletOnlyOnce(): void
    {
        $placeholder = new LazyPagelet('feed', '/pagelets/feed');

        (string) $placeholder;
        (string) $placeholder;

        $this->assertCount(1, BigPipe::jsmods()['require']);
    }

    public function testAddsTheCallToThePageletThatPrintsIt(): void
    {
        $feed = new FeedPagelet();

        $data = $feed->renderData();

        $this->assertStringContainsString('<div id="pagelet_post_comments"></div>', $data['domops'][0][3]['__html']);
        $this->assertSame(
            [$this->loadCall('/pagelets/comments', 'pagelet_post_comments', ['post' => 7])],
            $data['jsmods']['require']
        );
        $this->assertSame([], BigPipe::jsmods()['require']);
    }

    public function testAddsTheCallToTheResponseThatPrintsIt(): void
    {
        $response = new AsyncResponse();
        $response->appendContent('ul.posts', '<li>Post</li>' . new LazyPagelet('feed_page_2', '/feed?page=2'));

        $this->assertSame(
            [$this->loadCall('/feed?page=2', 'pagelet_feed_page_2')],
            $response->getResponse()['jsmods']['require']
        );
    }

    public function testTakesTheIdOfAPageletClass(): void
    {
        $this->assertSame('post_comments', CommentsPagelet::lazy('/pagelets/comments')->getId());
        $this->assertSame('feed', FeedPagelet::lazy('/pagelets/feed')->getId());
    }

    public function testRejectsAnInvalidId(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        new LazyPagelet('my feed', '/pagelets/feed');
    }

    public function testAPlainPageletHasNoIdForLazy(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        Pagelet::lazy('/pagelets/feed');
    }
}
