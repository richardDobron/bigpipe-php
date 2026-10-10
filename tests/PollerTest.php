<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use dobron\BigPipe\Poller;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PollerTest extends TestCase
{
    public function testStartsThePollerWithThePage(): void
    {
        $poller = (new Poller('/notifications/poll', 30000))
            ->setData(['since' => 7])
            ->setMaxRequests(10)
            ->setMuteWhenIdle(60000)
            ->start();

        $this->assertMatchesRegularExpression('/^poller_u_0_\d+$/', $poller->getId());
        $this->assertStringContainsString(
            '["bigpipe-util\/dist\/Poller",null,[{"id":"' . $poller->getId() . '","uri":"\/notifications\/poll","method":"GET","interval":30000,"muteWhenHidden":true,"clearOnQuicklingEvents":true,"data":{"since":7},"maxRequests":10,"muteWhenIdle":60000}]]',
            BigPipe::render()
        );
    }

    public function testStartsThePollerWithThePageletBeingRendered(): void
    {
        $pagelet = (new Pagelet('notifications'))->defer(function (): void {
            (new Poller('/notifications/poll', 30000))->start();
        });

        $data = $pagelet->renderData();

        $this->assertSame('bigpipe-util/dist/Poller', $data['jsmods']['require'][0][0]);
    }

    public function testRecognizesTheRequestOfAPoller(): void
    {
        $this->assertNull(Poller::requestedId());

        $_REQUEST['__poller'] = 'poller_u_0_3';
        $this->assertSame('poller_u_0_3', Poller::requestedId());

        $_REQUEST['__poller'] = 'Feed';
        $this->assertNull(Poller::requestedId());
    }

    public function testControlsThePollerFromTheResponse(): void
    {
        $_REQUEST['__poller'] = 'poller_u_0_3';

        $response = (new AsyncResponse())->call(Poller::requestedId(), 'setInterval', [60000]);

        $this->assertSame([['poller_u_0_3', 'setInterval', [60000]]], $response->getResponse()['jsmods']['require']);
    }
}
