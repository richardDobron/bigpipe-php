<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ParallelTest extends TestCase
{
    /** @var string[] */
    private array $chunks = [];

    protected function setUp(): void
    {
        if (!class_exists('Fiber')) {
            $this->markTestSkipped('Fibers need PHP 8.1.');
        }

        // PHPUnit hands an empty fiber.stack_size to the processes it isolates tests in.
        if ((int) ini_get('fiber.stack_size') < 8192) {
            ini_set('fiber.stack_size', '2M');
        }

        BigPipe::setParallel(true);
    }

    private function stream(): float
    {
        $start = microtime(true);

        BigPipe::stream(function (string $chunk): void {
            $this->chunks[] = $chunk;
        });

        return microtime(true) - $start;
    }

    /**
     * @return array<string, array>
     */
    private function sent(?string $script = null): array
    {
        preg_match_all(
            '/onPageletArrive\((\{.*?\})\);(?:<\/script>|\r?\n|\(new )/',
            $script ?? implode('', $this->chunks),
            $matches
        );

        $sent = [];
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $sent[$data['id']] = $data;
        }

        return $sent;
    }

    private function content(array $data): string
    {
        return $data['domops'][0][3]['__html'] ?? '';
    }

    public function testRendersThePageletsWhileTheyWait(): void
    {
        foreach (['a', 'b', 'c'] as $id) {
            (new Pagelet($id))->defer(function () use ($id) {
                Pagelet::sleep(0.2);

                return $id;
            });
        }

        $elapsed = $this->stream();

        $this->assertLessThan(0.5, $elapsed);
        $this->assertEqualsCanonicalizing(
            ['a', 'b', 'c'],
            array_values(array_map([$this, 'content'], array_slice($this->sent(), 0, 3)))
        );
    }

    public function testSendsAPageletAsSoonAsItIsRendered(): void
    {
        (new Pagelet('slow'))->defer(function () {
            Pagelet::sleep(0.2);

            return 'slow';
        });
        (new Pagelet('fast'))->defer(function () {
            Pagelet::sleep(0.02);

            return 'fast';
        });

        $this->stream();

        $this->assertSame(['fast', 'slow', BigPipe::LAST_PAGELET_ID], array_keys($this->sent()));
    }

    public function testSendsThePageletsOfALowerPhaseFirst(): void
    {
        (new Pagelet('sidebar'))->setPhase(1)->defer(fn () => 'sidebar');
        (new Pagelet('feed'))->defer(function () {
            Pagelet::sleep(0.1);

            return 'feed';
        });

        $this->stream();

        $this->assertSame(['feed', 'sidebar', BigPipe::LAST_PAGELET_ID], array_keys($this->sent()));
    }

    public function testKeepsTheOutputOfEveryPageletApart(): void
    {
        foreach (['a' => 0.1, 'b' => 0.02] as $id => $delay) {
            (new Pagelet($id))->defer(function () use ($id, $delay) {
                echo "$id-before ";
                Pagelet::sleep($delay);
                echo "$id-after ";

                return "$id-returned";
            });
        }

        $this->stream();

        $sent = $this->sent();
        $this->assertSame('a-before a-after a-returned', $this->content($sent['a']));
        $this->assertSame('b-before b-after b-returned', $this->content($sent['b']));
    }

    public function testKnowsTheCurrentPageletAfterWaiting(): void
    {
        $seen = [];

        foreach (['a' => 0.06, 'b' => 0.02] as $id => $delay) {
            (new Pagelet($id))->defer(function (Pagelet $pagelet) use ($id, $delay, &$seen) {
                $seen[$id][] = Pagelet::current() === $pagelet;
                Pagelet::sleep($delay);
                $seen[$id][] = Pagelet::current() === $pagelet;
                $pagelet->call('Module', null, [$id]);
            });
        }

        $this->stream();

        $this->assertSame(['a' => [true, true], 'b' => [true, true]], $seen);
        $sent = $this->sent();
        $this->assertSame([['Module', null, ['a']]], $sent['a']['jsmods']['require']);
        $this->assertSame([['Module', null, ['b']]], $sent['b']['jsmods']['require']);
    }

    public function testPageletsCanWaitForEachOther(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $writer = stream_socket_client('tcp://' . stream_socket_get_name($server, false));
        $reader = stream_socket_accept($server);
        stream_set_blocking($reader, false);

        (new Pagelet('consumer'))->defer(function () use ($reader) {
            return Pagelet::await(function () use ($reader) {
                $data = fread($reader, 100);

                return $data === '' || $data === false ? null : $data;
            }, [$reader], 2.0);
        });
        (new Pagelet('producer'))->defer(function () use ($writer) {
            Pagelet::sleep(0.05);
            fwrite($writer, 'payload');

            return 'sent';
        });

        $this->stream();

        $sent = $this->sent();
        $this->assertSame('payload', $this->content($sent['consumer']));
        $this->assertSame('sent', $this->content($sent['producer']));
    }

    public function testAFailingWaitFallsBack(): void
    {
        $reported = [];
        BigPipe::setErrorHandler(function (Throwable $exception) use (&$reported): void {
            $reported[] = $exception->getMessage();
        });

        (new Pagelet('broken'))->setFallback('fallback')->defer(function () {
            return Pagelet::await(function () {
                throw new RuntimeException('query failed');
            });
        });
        (new Pagelet('timeout'))->setFallback('late')->defer(fn () => Pagelet::await(fn () => null, [], 0.03));
        (new Pagelet('fine'))->defer(fn () => 'fine');

        $this->stream();

        $sent = $this->sent();
        $this->assertSame('fallback', $this->content($sent['broken']));
        $this->assertSame('late', $this->content($sent['timeout']));
        $this->assertSame('fine', $this->content($sent['fine']));
        $this->assertEqualsCanonicalizing(['query failed', 'Timed out waiting.'], $reported);
    }

    public function testAPageletCannotWaitInsideAnOutputBuffer(): void
    {
        $reported = [];
        BigPipe::setErrorHandler(function (Throwable $exception) use (&$reported): void {
            $reported[] = get_class($exception);
        });

        (new Pagelet('feed'))->setFallback('fallback')->defer(function () {
            ob_start();

            try {
                Pagelet::sleep(0.01);
            } finally {
                ob_end_clean();
            }
        });

        $this->stream();

        $this->assertSame([LogicException::class], $reported);
        $this->assertSame('fallback', $this->content($this->sent()['feed']));
    }

    public function testSendsPageletsCreatedWhileRenderingAnother(): void
    {
        (new Pagelet('content'))->defer(function () {
            Pagelet::sleep(0.01);
            (new Pagelet('inner'))->defer(fn () => 'inner');

            return 'content';
        });

        $this->stream();

        $this->assertSame(['content', 'inner', BigPipe::LAST_PAGELET_ID], array_keys($this->sent()));
    }

    public function testRenderRendersThePageletsWhileTheyWait(): void
    {
        foreach (['a', 'b', 'c'] as $id) {
            (new Pagelet($id))->defer(function () use ($id) {
                Pagelet::sleep(0.2);

                return $id;
            });
        }

        $start = microtime(true);
        $script = BigPipe::render();
        $elapsed = microtime(true) - $start;

        $sent = $this->sent($script);
        $this->assertLessThan(0.5, $elapsed);
        $this->assertSame(['a', 'b', 'c'], array_keys($sent));
        $this->assertSame('a', $this->content($sent['a']));
        $this->assertArrayNotHasKey('is_last', $sent['a']);
        $this->assertTrue($sent['c']['is_last'] ?? false);
    }

    public function testWaitingOutsideOfAParallelPageBlocks(): void
    {
        BigPipe::setParallel(false);
        $count = 0;

        $result = Pagelet::await(function () use (&$count) {
            return ++$count < 3 ? null : 'ready';
        });

        $this->assertSame('ready', $result);

        $start = microtime(true);
        Pagelet::sleep(0.05);
        $this->assertGreaterThanOrEqual(0.05, microtime(true) - $start);
    }

    public function testParallelCanBeTurnedOff(): void
    {
        BigPipe::setParallel(false);

        foreach (['a', 'b'] as $id) {
            (new Pagelet($id))->defer(function () use ($id) {
                Pagelet::sleep(0.1);

                return $id;
            });
        }

        $this->assertGreaterThanOrEqual(0.2, $this->stream());
        $this->assertFalse(BigPipe::isParallel());
    }

    private function slowPagelets(): void
    {
        foreach (['a', 'b', 'c'] as $id) {
            (new Pagelet($id))->defer(function () use ($id) {
                Pagelet::sleep(0.2);

                return $id;
            });
        }
    }

    public function testAResponseRendersItsPageletsWhileTheyWait(): void
    {
        $this->slowPagelets();
        $response = (new AsyncResponse())->transition('<p>content</p>', 'Title');

        $start = microtime(true);
        $data = $response->getResponse();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.5, $elapsed);
        $this->assertEqualsCanonicalizing(['a', 'b', 'c'], array_column($data['pagelets'], 'id'));
        $this->assertTrue(end($data['pagelets'])['is_last']);
    }

    public function testAStreamedResponseSendsItsPageletsAsTheyAreRendered(): void
    {
        (new Pagelet('slow'))->defer(function () {
            Pagelet::sleep(0.2);

            return 'slow';
        });
        (new Pagelet('fast'))->defer(function () {
            Pagelet::sleep(0.02);

            return 'fast';
        });
        $this->slowPagelets();
        $parts = [];
        $response = (new AsyncResponse())->transition('<p>content</p>', 'Title');

        $start = microtime(true);
        $response->stream(function (string $part) use (&$parts): void {
            $parts[] = json_decode(rtrim($part, AsyncResponse::STREAM_DELIMITER), true)['content'];
        });
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.55, $elapsed);
        $ids = [];
        foreach ($parts as $content) {
            foreach ($content['pagelets'] ?? [] as $pagelet) {
                $ids[] = $pagelet['id'];
            }
        }
        $this->assertSame('fast', $ids[0]);
        $this->assertSame(BigPipe::LAST_PAGELET_ID, end($ids));
        $this->assertCount(6, $ids);
    }
}
