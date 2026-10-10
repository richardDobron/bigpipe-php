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
                        'bigpipe-util/dist/core/Dialog', 'showFromModel', [
                            [
                                'title' => 'Dialog title',
                                'body' => 'html <strong>content</strong>',
                                'footer' => '<button>close</button>',
                                'controller' => 'ModalMonitor'
                            ],
                            [],
                        ]
                    ],
                ],
            ],
            '__ar' => 1,
        ]);
    }
    public function testOptions(): void
    {
        $response = new DialogResponse();

        $response->setBody('Body')
            ->setBackdrop('static')
            ->setKeyboard(false)
            ->setAutoFocus(false)
            ->setTrapFocus()
            ->setRefocus()
            ->setHideOnTransition(false)
            ->setHideOnSuccess('form')
            ->setCausalElement('opener')
            ->setPosition(80)
            ->setOption('transition', 300)
            ->dialog();

        $args = $response->getResponse()['jsmods']['require'][0][2];

        $this->assertSame([
            'backdrop' => 'static',
            'keyboard' => false,
            'autoFocus' => false,
            'trapFocus' => true,
            'refocus' => true,
            'hideOnTransition' => false,
            'hideOnSuccess' => 'form',
            'causalElement' => ['__e' => 'opener'],
            'position' => ['top' => 80],
            'transition' => 300,
            'title' => null,
            'body' => 'Body',
            'footer' => null,
            'controller' => null,
        ], $args[0]);
    }

    public function testPositionAndOptionsPassedToDialog(): void
    {
        $response = new DialogResponse();

        $response->setBody('Body')
            ->setPosition(null, true, true)
            ->setHideOnSuccess()
            ->dialog(['hideOnSuccess' => false]);

        $options = $response->getResponse()['jsmods']['require'][0][2][0];

        $this->assertSame(['centered' => true, 'ignoreTopInShortViewport' => true], $options['position']);
        $this->assertFalse($options['hideOnSuccess']);
    }

    public function testDefaultPosition(): void
    {
        $response = new DialogResponse();

        $response->setBody('Body')->setPosition()->dialog();

        $this->assertTrue($response->getResponse()['jsmods']['require'][0][2][0]['position']);
    }
}
