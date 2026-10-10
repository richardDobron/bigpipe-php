<?php

declare(strict_types=1);

namespace AsyncResponsePageletTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class FeedPagelet extends Pagelet
{
    protected array $css = ['/css/feed.css'];

    protected function content(): string
    {
        $this->require(['Feed', 'init']);

        return '<ul class="posts"></ul>';
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class AsyncResponsePageletTest extends TestCase
{
    public function testSendsThePageletInThePlaceOfTheElementThatSentTheRequest(): void
    {
        $response = (new AsyncResponse())->pagelet(new FeedPagelet());
        $data = $response->getResponse();

        $this->assertSame([['replace', '', true, ['__html' => '<div id="pagelet_feed"></div>']]], $data['domops']);
        $this->assertSame([[
            'id' => 'feed',
            'js' => [],
            'css' => ['/css/feed.css'],
            'domops' => [['setContent', '#pagelet_feed', false, ['__html' => '<ul class="posts"></ul>']]],
            'jsmods' => ['require' => [['Feed', 'init']]],
            'is_last' => true,
        ]], $data['pagelets']);
    }

    public function testReplacesTheElementMatchingTheSelector(): void
    {
        $response = (new AsyncResponse())->pagelet(new FeedPagelet(), '#feed-placeholder');

        $this->assertSame(['replace', '#feed-placeholder', false], array_slice($response->getResponse()['domops'][0], 0, 3));
    }

    public function testIsNotSentWithThePageToo(): void
    {
        (new AsyncResponse())->pagelet(new FeedPagelet());

        $this->assertSame([], BigPipe::context()->pagelets);
    }

    public function testRefreshesThePageletWithTheSameId(): void
    {
        $data = (new AsyncResponse())->refreshPagelet(new FeedPagelet())->getResponse();

        $this->assertSame(
            ['replace', '#pagelet_feed', false, ['__html' => '<div id="pagelet_feed"></div>']],
            $data['domops'][0]
        );
        $this->assertSame('feed', $data['pagelets'][0]['id']);
    }

    public function testSendsNoPageletsFieldWithoutPagelets(): void
    {
        $this->assertArrayNotHasKey('pagelets', (new AsyncResponse())->getResponse());
    }

    public function testRendersPlainPageletsToo(): void
    {
        $pagelet = (new Pagelet('notice'))->appendContent('<p>Saved</p>');

        $data = (new AsyncResponse())->pagelet($pagelet)->getResponse();

        $this->assertSame('<p>Saved</p>', $data['pagelets'][0]['domops'][0][3]['__html']);
    }
}
