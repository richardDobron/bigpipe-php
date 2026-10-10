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
class QuicklingTest extends TestCase
{
    public function testTurnsPageTransitionsOnForThePage(): void
    {
        Quickling::configure('2026-10-10', '^/admin', ['print'], 50);

        Quickling::init('content');

        $this->assertStringContainsString(
            '["bigpipe-util\/dist\/Quickling","init",[{"__e":"content"},{"version":"2026-10-10","inactivePageRegex":"^\/admin","badRequestKeys":["print"],"sessionLength":50}]]',
            BigPipe::render()
        );
    }

    public function testRecognizesAPageTransition(): void
    {
        $this->assertFalse(Quickling::isRequested());

        $_GET['quickling'] = ['version' => '1'];

        $this->assertTrue(Quickling::isRequested());
    }

    public function testAnswersAPageTransition(): void
    {
        Quickling::configure('v1');
        $_SERVER['REQUEST_URI'] = '/feed?page=2&quickling%5Bversion%5D=v1&__req=3';
        $ads = (new Pagelet('ads'))->setPhase(1);
        $feed = new Pagelet('feed');

        $response = (new AsyncResponse())
            ->transition("<main>$feed</main><aside>$ads</aside>", 'Feed', 'feed')
            ->getResponse();

        $this->assertSame(
            ['title' => 'Feed', 'body_class' => 'feed', 'version' => 'v1', 'uri' => '/feed?page=2'],
            $response['payload']
        );
        $this->assertSame(
            [['setContent', '', true, ['__html' => '<main><div id="pagelet_feed"></div></main><aside><div id="pagelet_ads"></div></aside>']]],
            $response['domops']
        );
        $this->assertSame(['feed', 'ads'], array_column($response['pagelets'], 'id'));
        $this->assertTrue($response['pagelets'][1]['is_last']);
        $this->assertArrayNotHasKey('is_last', $response['pagelets'][0]);
    }

    public function testRedirectsAPageTransition(): void
    {
        $this->assertSame(['redirect' => '/new'], (new AsyncResponse())->transitionRedirect('/new')->getResponse()['payload']);
        $this->assertSame(
            ['redirect' => '/login', 'force' => true],
            (new AsyncResponse())->transitionRedirect('/login', true)->getResponse()['payload']
        );
    }
}
