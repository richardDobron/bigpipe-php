<?php

declare(strict_types=1);

namespace DialogResponseTest;

use dobron\BigPipe\DialogResponse;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DialogResponseTest extends TestCase
{
    public function testDialog(): void
    {
        $response = new DialogResponse();

        $response->setTitle('Dialog title')
            ->setController("require('ModalMonitor')")
            ->setBody('html <strong>content</strong>')
            ->setFooter('<button>close</button>')
            ->dialog();

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'bigpipe-util/src/core/Dialog', 'showFromModel', [
                            [
                                'title' => 'Dialog title',
                                'body' => 'html <strong>content</strong>',
                                'footer' => '<button>close</button>',
                                'controller' => 'ModalMonitor'
                            ]
                        ]
                    ]
                ],
            ],
            '__ar' => 1,
        ]);
    }
}
