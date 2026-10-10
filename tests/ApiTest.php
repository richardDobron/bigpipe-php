<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use dobron\BigPipe\DialogResponse;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use dobron\BigPipe\TransportMarker;
use PHPUnit\Framework\TestCase;

use function dobron\BigPipe\array_trim;
use function dobron\BigPipe\generate_unique_node_id;

/**
 * @runTestsInSeparateProcesses
 */
class ApiTest extends TestCase
{
    public function testTransportMarkersHaveShortNamesAndKeepTheOldOnes(): void
    {
        $this->assertSame(['__e' => 'chart'], TransportMarker::element('chart'));
        $this->assertSame(['__m' => 'Chart'], TransportMarker::module('Chart'));
        $this->assertSame(['__map' => [['a', 1]]], TransportMarker::map([['a', 1]]));
        $this->assertSame(['__set' => [1, 2]], TransportMarker::set([1, 2]));
        $this->assertSame(['__html' => '<b>x</b>'], TransportMarker::html('<b>x</b>'));

        $this->assertSame(TransportMarker::element('chart'), TransportMarker::transportElement('chart'));
        $this->assertSame(TransportMarker::html('x'), (new AsyncResponse())->transport()->transportHtml('x'));
    }

    public function testCallsModulesExplicitly(): void
    {
        BigPipe::page()
            ->call('Page', 'init')
            ->call('UserLoggedInAlert', null, ['Marvin'])
            ->call('Chart', 'render', [TransportMarker::element('chart')], -1);

        $this->assertSame([
            ['Chart', 'render', [['__e' => 'chart']]],
            ['Page', 'init'],
            ['UserLoggedInAlert', null, ['Marvin']],
        ], BigPipe::jsmods()['require']);
    }

    public function testRejectsAnEmptyModule(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        BigPipe::page()->call('');
    }

    public function testTheResponseCallsDefinesAndCreatesInstancesItself(): void
    {
        $response = new AsyncResponse();
        $chart = $response
            ->call('Feed', 'init', [1])
            ->define('SiteData', ['locale' => 'sk_SK'])
            ->instance('Chart');

        $jsmods = $response->getResponse()['jsmods'];

        $this->assertSame([['Feed', 'init', [1]]], $jsmods['require']);
        $this->assertSame([['SiteData', ['locale' => 'sk_SK']]], $jsmods['define']);
        $this->assertSame([[$chart->getId(), 'Chart']], $jsmods['instances']);
    }

    public function testAppendsAFileAndPrintsThePlaceholder(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'pagelet');
        file_put_contents($file, '<p><?= "from a file" ?></p>');

        $pagelet = (new Pagelet('notice'))->appendFile($file);
        unlink($file);

        $this->assertSame('<p>from a file</p>', $pagelet->renderContent());
        $this->assertSame('<div id="pagelet_notice"></div>', $pagelet->placeholder());
        $this->assertSame($pagelet->placeholder(), $pagelet->render());
    }

    public function testDialogControllerCanBeAModuleName(): void
    {
        $response = (new DialogResponse())
            ->setDialog('<div class="modal"></div>')
            ->setController('PostEditor', [7])
            ->dialog();

        $call = $response->getResponse()['jsmods']['require'][0];

        $this->assertSame([DialogResponse::DIALOG_MODULE, 'render'], [$call[0], $call[1]]);
        $this->assertSame('PostEditor', $call[2][0]['controller']);
        $this->assertSame([7], $call[2][1]);
    }

    public function testInvalidArgumentsAreInvalidArgumentExceptions(): void
    {
        $this->assertInstanceOf(
            InvalidArgumentException::class,
            new BigPipeInvalidArgumentException('invalid')
        );
    }

    public function testCountsNodeIdsPerRequestContext(): void
    {
        $this->assertSame('u_0_0', generate_unique_node_id());
        $this->assertSame('u_0_1', generate_unique_node_id());

        BigPipe::withContext(function () {
            $this->assertSame('u_0_0', generate_unique_node_id());
        }, new Context());
    }

    public function testArrayTrimStopsAtAnEmptyArray(): void
    {
        $this->assertSame(['Module', null, [1]], array_trim(['Module', null, [1], null, []]));
        $this->assertSame([], array_trim(['', null]));
    }
}
