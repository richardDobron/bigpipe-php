<?php

declare(strict_types=1);

namespace AsyncResponseTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AnonymousTestClass
{
    public function __toString()
    {
        return thisFunctionDoesNotExist();
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class AsyncResponseTest extends TestCase
{
    public function testBigPipe(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require(
            "require('someFunction')",
            [
                'abc'
            ]
        );

        $response->bigPipe()->require(
            "require('Composer').init()",
            [
                123
            ]
        );

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'someFunction',
                        null,
                        [
                            'abc'
                        ]
                    ],
                    [
                        'Composer',
                        'init',
                        [
                            123
                        ]
                    ]
                ]
            ],
            '__ar' => 1,
        ]);
    }

    public function testOmittingEmptyMethodName(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require("require('someFunction')");

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'someFunction',
                    ],
                ]
            ],
            '__ar' => 1,
        ]);
    }

    public function testAsyncResponse(): void
    {
        $response = new AsyncResponse();

        $response->setContent('#element', '<p>paragraph</p>');

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [
                [
                    'setContent',
                    '#element',
                    false,
                    [
                        '__html' => '<p>paragraph</p>',
                    ]
                ]
            ],
            'jsmods' => [
                'require' => [],
            ],
            '__ar' => 1,
        ]);
    }

    public function testExceptionInRequire(): void
    {
        $response = new AsyncResponse();

        try {
            $response->bigPipe()->require("require('failedModule').init()", [
                new AnonymousTestClass(),
            ]);
        } catch (\Throwable) {
        }

        $response->bigPipe()->require("require('successFunction')");

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    ['successFunction'],
                ]
            ],
            '__ar' => 1,
        ]);
    }

    public function testReplaceWithoutSelector(): void
    {
        $response = new AsyncResponse();

        $response->replace('', '<p>self replace</p>');

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [
                [
                    'replace',
                    '',
                    true,
                    [
                        '__html' => '<p>self replace</p>',
                    ],
                ]
            ],
            'jsmods' => [
                'require' => [],
            ],
            '__ar' => 1,
        ]);
    }

    public function testException(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        $response = new AsyncResponse();

        $response->bigPipe()->require("InvalidUsage.method()");
    }
}
