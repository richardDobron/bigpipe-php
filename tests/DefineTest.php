<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Bootloader;
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

    public function testRendersTheDefinesBeforeThePagelets(): void
    {
        (new BigPipe())->define('SiteData', ['locale' => 'sk_SK'])->require(['Page', 'init']);
        (new Pagelet('feed'))->require(['Feed', 'init']);

        $lines = explode("\n", trim(strip_tags(BigPipe::render())));

        $this->assertSame(
            '(window.__bigpipe=window.__bigpipe||[]).push(["handle",{"define":[["SiteData",{"locale":"sk_SK"}]]}]);',
            trim($lines[0])
        );
        $this->assertStringContainsString('["arrive",{"id":"feed"', $lines[1]);
        $this->assertSame(
            '(window.__bigpipe=window.__bigpipe||[]).push(["handle",{"require":[["Page","init"]]}]);',
            trim($lines[2])
        );
    }

    public function testRejectsAnEmptyModule(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new BigPipe())->define('', []);
    }

    public function testDefinesAnElementAsAModule(): void
    {
        $response = (new AsyncResponse())->defineElement('ChartBox', 'chart-box');

        $this->assertSame(
            [['ChartBox', ['__e' => 'chart-box']]],
            $response->getResponse()['jsmods']['define']
        );
    }

    public function testAPageletDefinesElementsToo(): void
    {
        $pagelet = (new Pagelet('feed'))->defineElement('FeedList', 'feed-list');

        $this->assertSame([['FeedList', ['__e' => 'feed-list']]], $pagelet->jsmods()['define']);
    }

    public function testAnElementNeedsAnId(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new BigPipe())->defineElement('ChartBox', '');
    }

    public function testDefinesTheModuleTheServerChose(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()
            ->defineCond('Editor', 'EditorNext')
            ->defineCond('Chat', null);

        $this->assertSame([
            ['Editor', ['__rc' => 'EditorNext']],
            ['Chat', ['__rc' => null]],
        ], $response->getResponse()['jsmods']['define']);
    }

    public function testTheResponseDefinesTheModuleTheServerChoseToo(): void
    {
        $response = (new AsyncResponse())
            ->defineCond('Editor', 'EditorNext')
            ->defineCond('Chat', null);

        $this->assertSame([
            ['Editor', ['__rc' => 'EditorNext']],
            ['Chat', ['__rc' => null]],
        ], $response->getResponse()['jsmods']['define']);
    }

    public function testSendsTheResourcesOfTheModuleTheServerChose(): void
    {
        Bootloader::setResourceMap(['editor-next.js' => ['type' => 'js', 'src' => '/static/editor-next.js']]);
        Bootloader::enableBootload(['EditorNext' => ['editor-next.js']]);
        $response = new AsyncResponse();
        $response->bigPipe()->defineCond('Editor', 'EditorNext');

        $data = $response->getResponse();

        $this->assertSame(['EditorNext' => ['editor-next.js']], $data['bootloadable']);
        $this->assertSame(['editor-next.js'], array_keys($data['resource_map']));
    }

    public function testSendsTheResourcesOfTheModuleTheServerChoseBeforeThePagelets(): void
    {
        Bootloader::enableBootload(['EditorNext' => ['/static/editor-next.js']]);
        BigPipe::page()->defineCond('Editor', 'EditorNext');
        (new Pagelet('composer'))->call('Editor', 'open');

        $output = BigPipe::render();

        $bootload = strpos($output, '"enableBootload",[{"EditorNext":["\/static\/editor-next.js"]}]');
        $this->assertNotFalse($bootload);
        $this->assertLessThan(strpos($output, '["Editor",{"__rc":"EditorNext"}]'), $bootload);
        $this->assertLessThan(strpos($output, '["arrive",'), strpos($output, '["Editor",{"__rc":"EditorNext"}]'));
    }

    public function testSendsTheResourcesOfTheModuleTheServerChoseWithTheFirstPartOfAStream(): void
    {
        Bootloader::enableBootload(['EditorNext' => ['/static/editor-next.js']]);
        $response = new AsyncResponse();
        $response->bigPipe()->defineCond('Editor', 'EditorNext');

        $chunks = '';
        $response->stream(function (string $part) use (&$chunks): void {
            $chunks .= $part;
        });
        $first = json_decode(explode(AsyncResponse::STREAM_DELIMITER, $chunks)[0], true)['content'];

        $this->assertSame(['EditorNext' => ['/static/editor-next.js']], $first['bootloadable']);
        $this->assertSame([['Editor', ['__rc' => 'EditorNext']]], $first['jsmods']['define']);
    }

    public function testRejectsAnInvalidChoice(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new BigPipe())->defineCond('Editor', 'Editor');
    }
}
