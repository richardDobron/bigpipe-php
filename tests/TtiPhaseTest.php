<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TtiPhaseTest extends TestCase
{
    public function testPageletsCarryNoTtiPhaseByDefault(): void
    {
        $this->assertArrayNotHasKey('tti_phase', (new Pagelet('feed'))->renderData());
    }

    public function testPageletsCarryTheTtiPhase(): void
    {
        BigPipe::setTtiPhase(1);

        $feed = new Pagelet('feed');
        $ads = (new Pagelet('ads'))->setPhase(2);

        $this->assertSame(1, $feed->renderData()['tti_phase']);
        $this->assertSame(1, $ads->renderData()['tti_phase']);
        $this->assertSame(2, $ads->renderData()['phase']);
    }

    public function testThePageScriptSendsIt(): void
    {
        BigPipe::setTtiPhase(0);
        new Pagelet('feed');

        $this->assertStringContainsString('"tti_phase":0', BigPipe::render());
    }

    public function testAResponseSendsIt(): void
    {
        BigPipe::setTtiPhase(0);

        $response = (new AsyncResponse())->pagelet(new Pagelet('feed'));

        $this->assertSame(0, $response->getResponse()['pagelets'][0]['tti_phase']);
    }

    public function testItCanBeTurnedOff(): void
    {
        BigPipe::setTtiPhase(0);
        BigPipe::setTtiPhase(null);

        $this->assertArrayNotHasKey('tti_phase', (new Pagelet('feed'))->renderData());
    }
}
