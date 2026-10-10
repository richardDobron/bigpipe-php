<?php

declare(strict_types=1);

namespace MorePagerTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\MorePager;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class FeedPagelet extends Pagelet
{
    protected function content(): string
    {
        return '<ul class="posts"><li>Post</li></ul>' . new MorePager('/feed?page=2');
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class MorePagerTest extends TestCase
{
    public function testPrintsALinkAndCallsMorePagerFetchOnScrollWithIt(): void
    {
        $pager = new MorePager('/feed?page=2&sort="new"', 'More posts');

        $this->assertSame(
            '<a id="u_0_0" href="/feed?page=2&amp;sort=&quot;new&quot;" ajaxify="/feed?page=2&amp;sort=&quot;new&quot;" rel="async">More posts</a>',
            (string) $pager
        );
        $this->assertSame(
            [[MorePager::MODULE, null, [['__e' => 'u_0_0'], 300]]],
            BigPipe::jsmods()['require']
        );
    }

    public function testCallsTheModuleOnlyOnce(): void
    {
        $pager = new MorePager('/feed?page=2', 'More', 0);

        (string) $pager;
        (string) $pager;

        $this->assertSame([[MorePager::MODULE, null, [['__e' => 'u_0_0'], 0]]], BigPipe::jsmods()['require']);
    }

    public function testAddsTheCallToThePageletThatPrintsIt(): void
    {
        $data = (new FeedPagelet())->renderData();

        $this->assertSame([[MorePager::MODULE, null, [['__e' => 'u_0_0'], 300]]], $data['jsmods']['require']);
        $this->assertSame([], BigPipe::jsmods()['require']);
    }

    public function testReplacesItselfWithThePagerOfTheNextPage(): void
    {
        $response = new AsyncResponse();
        $response
            ->appendContent('ul.posts', '<li>Post</li>')
            ->replace('', (string) new MorePager('/feed?page=3'));

        $data = $response->getResponse();

        $this->assertSame(['replace', '', true], array_slice($data['domops'][1], 0, 3));
        $this->assertSame([[MorePager::MODULE, null, [['__e' => 'u_0_0'], 300]]], $data['jsmods']['require']);
    }
}
