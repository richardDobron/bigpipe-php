<?php

declare(strict_types=1);

namespace AsyncResponseTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\DialogResponse;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AnonymousTestClass
{
    public function __toString()
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require(['module']);

        return thisFunctionDoesNotExist();
    }
}

class TestClass
{
    public function __toString()
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require(['second'], [123]);

        return 'test';
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

    public function testProxyRequire(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require()->someFunction(['abc']);

        $response->bigPipe()->require(priority: 0)->Composer()->init([123]);

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'Composer',
                        'init',
                        [
                            123
                        ]
                    ],
                    [
                        'someFunction',
                        null,
                        [
                            'abc'
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

    public function testRequireInRequire(): void
    {
        $response = new DialogResponse();

        $response->bigPipe()->require("require('first').init()");

        $response->setDialog(new TestClass())
            ->dialog([
                'backdrop' => 'static',
                'keyboard' => false,
            ]);

        $response->bigPipe()->require("require('third')");

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'first', 'init'
                    ],
                    [
                        'bigpipe-util/src/core/Dialog', 'render', [
                            [
                                'backdrop' => 'static',
                                'keyboard' => false,
                                'content' => 'test',
                                'controller' => null,
                            ],
                            []
                        ]
                    ],
                    [
                        'second', null, [123]
                    ],
                    [
                        'third'
                    ],
                ]
            ],
            '__ar' => 1,
        ]);
    }

    public function testPriorityInRequire(): void
    {
        $response = new AsyncResponse();

        $response->bigPipe()->require("require('someLowPriorityFunction')", [20], 20);
        $response->bigPipe()->require("require('someMediumPriorityFunction')", [3], 3);
        $response->bigPipe()->require("require('somePriorityFunction')", [0], 0);
        $response->bigPipe()->require("require('someSuperHighPriorityFunction')", [-1], -1);
        $response->bigPipe()->require("require('someFunctionWithDefaultPriority')", [1]);
        $response->bigPipe()->require("require('someFunctionWithDefaultPriority')", [2]);
        $response->bigPipe()->require("require('someFunctionWithDefaultPriority')", [3]);
        $response->bigPipe()->require("require('someFunctionWithDefaultPriority')", [4]);

        $this->assertEquals($response->getResponse(), [
            'payload' => [],
            'domops' => [],
            'jsmods' => [
                'require' => [
                    [
                        'someSuperHighPriorityFunction',
                        null,
                        [
                            -1,
                        ],
                    ],
                    [
                        'somePriorityFunction',
                        null,
                        [
                            0,
                        ],
                    ],
                    [
                        'someMediumPriorityFunction',
                        null,
                        [
                            3,
                        ],
                    ],
                    [
                        'someFunctionWithDefaultPriority',
                        null,
                        [
                            1,
                        ],
                    ],
                    [
                        'someFunctionWithDefaultPriority',
                        null,
                        [
                            2,
                        ],
                    ],
                    [
                        'someFunctionWithDefaultPriority',
                        null,
                        [
                            3,
                        ],
                    ],
                    [
                        'someFunctionWithDefaultPriority',
                        null,
                        [
                            4,
                        ],
                    ],
                    [
                        'someLowPriorityFunction',
                        null,
                        [
                            20,
                        ],
                    ],],],
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
