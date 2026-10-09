<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use dobron\BigPipe\TransportMarker;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DefineTest extends TestCase
{
    public function testDefinesModules(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()
            ->define('SiteData', ['locale' => 'sk_SK', 'user' => ['id' => 7]])
            ->define('ChartContainer', TransportMarker::transportElement('chart'))
            ->require("require('Dashboard').init()", [TransportMarker::transportModule('SiteData')]);

        $jsmods = $response->getResponse()['jsmods'];

        $this->assertSame([
            ['SiteData', ['locale' => 'sk_SK', 'user' => ['id' => 7]]],
            ['ChartContainer', ['__e' => 'chart']],
        ], $jsmods['define']);
        $this->assertSame([['Dashboard', 'init', [['__m' => 'SiteData']]]], $jsmods['require']);
    }

    public function testDefinesAnInstanceAsPartOfTheExports(): void
    {
        $bigPipe = new BigPipe();
        $chart = $bigPipe->instance('Chart');
        $bigPipe->define('ChartConfig', ['chart' => $chart]);

        $this->assertSame(
            '[["ChartConfig",{"chart":{"__m":"__inst_u_0_0"}}]]',
            json_encode(BigPipe::jsmods()['define'])
        );
    }

    public function testPageletDefinesAreSentWithThePagelet(): void
    {
        $pagelet = new Pagelet('feed');
        $pagelet->define('FeedConfig', ['pageSize' => 20]);

        $this->assertSame([['FeedConfig', ['pageSize' => 20]]], $pagelet->renderData()['jsmods']['define']);
        $this->assertArrayNotHasKey('define', BigPipe::jsmods());
    }

    public function testSendsNoDefinesWhenNoneAreDefined(): void
    {
        $this->assertArrayNotHasKey('define', BigPipe::jsmods());
    }

    public function testRejectsAnEmptyModule(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new BigPipe())->define('', []);
    }
}
