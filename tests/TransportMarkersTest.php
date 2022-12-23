<?php

declare(strict_types=1);

namespace TransportMarkersTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TransportMarkersTest extends TestCase
{
    public function testAsyncResponse(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require("require('Chart').setup()", [
            'element' => TransportMarker::transportElement('chart-div'),
            'dataPoints' => $response->transport()->transportSet([
                ['x' => 10, 'y' => 71],
                ['x' => 20, 'y' => 55],
                ['x' => 30, 'y' => 50],
                ['x' => 40, 'y' => 65],
            ]),
            'dependency' => $response->transport()->transportModule('ChartRenderer'),
        ]);

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'Chart',
                        'setup',
                        [
                            'element' => ['__e' => 'chart-div'],
                            'dataPoints' => ['__set' => [
                                ['x' => 10, 'y' => 71],
                                ['x' => 20, 'y' => 55],
                                ['x' => 30, 'y' => 50],
                                ['x' => 40, 'y' => 65],
                            ]],
                            'dependency' => [
                                '__m' => 'ChartRenderer',
                            ],
                        ]
                    ]
                ],
            ],
            '__ar' => 1,
        ]);
    }
}
