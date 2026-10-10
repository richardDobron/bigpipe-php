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
class InstanceTest extends TestCase
{
    public function testDefinesAnInstanceAndCallsItsMethods(): void
    {
        $response = new AsyncResponse();

        $chart = $response->bigPipe()->instance('ChartRenderer', [
            TransportMarker::transportElement('chart'),
            [10, 20, 30],
        ]);
        $chart->call('render')->call('highlight', [2]);
        $response->bigPipe()->require("require('Dashboard').add()", [$chart]);

        $jsmods = json_decode(json_encode($response->getResponse()['jsmods']), true);

        $this->assertSame('__inst_u_0_0', $chart->getId());
        $this->assertSame([
            ['__inst_u_0_0', 'ChartRenderer', [['__e' => 'chart'], [10, 20, 30]]],
        ], $jsmods['instances']);
        $this->assertSame([
            ['__inst_u_0_0', 'render'],
            ['__inst_u_0_0', 'highlight', [2]],
            ['Dashboard', 'add', [['__m' => '__inst_u_0_0']]],
        ], $jsmods['require']);
    }

    public function testOmitsTheArgumentsWhenThereAreNone(): void
    {
        (new BigPipe())->instance('Clock');

        $this->assertSame([['__inst_u_0_0', 'Clock']], BigPipe::jsmods()['instances']);
    }

    public function testSendsNoInstancesWhenNoneAreDefined(): void
    {
        $this->assertArrayNotHasKey('instances', BigPipe::jsmods());
    }

    public function testPageletInstancesAreSentWithThePagelet(): void
    {
        $pagelet = new Pagelet('feed');
        $pagelet->instance('Feed')->call('start');

        $this->assertSame([
            'require' => [['__inst_u_0_0', 'start']],
            'instances' => [['__inst_u_0_0', 'Feed']],
        ], $pagelet->renderData()['jsmods']);
        $this->assertArrayNotHasKey('instances', BigPipe::jsmods());
    }

    public function testResetForgetsTheInstances(): void
    {
        (new BigPipe())->instance('Clock');
        BigPipe::reset();

        $this->assertArrayNotHasKey('instances', BigPipe::jsmods());
    }

    public function testRejectsAnEmptyModule(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new BigPipe())->instance('');
    }
}
