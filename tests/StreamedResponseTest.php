<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class StreamedResponseTest extends TestCase
{
    /** @var list<array{content: array, finished: bool}> */
    private array $parts = [];

    private function stream(AsyncResponse $response): void
    {
        $response->stream(function (string $part): void {
            $this->assertStringEndsWith(AsyncResponse::STREAM_DELIMITER, $part);
            $this->parts[] = json_decode(substr($part, 0, -strlen(AsyncResponse::STREAM_DELIMITER)), true);
        });
    }

    public function testRecognizesARequestThatAcceptsAStream(): void
    {
        $this->assertFalse(AsyncResponse::isStreamRequested());

        $_REQUEST['__stream'] = '1';

        $this->assertTrue(AsyncResponse::isStreamRequested());
    }

    public function testStreamsThePageletsOfAPageTransitionAsTheyAreRendered(): void
    {
        BigPipe::page()->define('Config', ['locale' => 'sk']);
        $feed = (new Pagelet('feed'))->defer(function () {
            $this->assertCount(1, $this->parts);

            return 'posts';
        });
        $ads = (new Pagelet('ads'))->setPhase(1)->defer(function () {
            $this->assertCount(2, $this->parts);

            return 'ads';
        });
        BigPipe::page()->call('Page', 'init');

        $response = (new AsyncResponse())->transition("<main>$feed</main><aside>$ads</aside>", 'Feed');
        $this->stream($response);

        $this->assertCount(4, $this->parts);
        $this->assertSame('Feed', $this->parts[0]['content']['payload']['title']);
        $this->assertSame([['Config', ['locale' => 'sk']]], $this->parts[0]['content']['jsmods']['define']);
        $this->assertStringContainsString('pagelet_feed', $this->parts[0]['content']['domops'][0][3]['__html']);
        $this->assertSame('feed', $this->parts[1]['content']['pagelets'][0]['id']);
        $this->assertSame('ads', $this->parts[2]['content']['pagelets'][0]['id']);
        $this->assertArrayNotHasKey('is_last', $this->parts[2]['content']['pagelets'][0]);
        $this->assertSame(BigPipe::LAST_PAGELET_ID, $this->parts[3]['content']['pagelets'][0]['id']);
        $this->assertTrue($this->parts[3]['content']['pagelets'][0]['is_last']);
        $this->assertSame([['Page', 'init']], $this->parts[3]['content']['jsmods']['require']);
        $this->assertSame([false, false, false, true], array_column($this->parts, 'finished'));
    }

    public function testStreamsAResponseWithoutPagelets(): void
    {
        $this->stream((new AsyncResponse())->setContent('#count', '3'));

        $this->assertCount(2, $this->parts);
        $this->assertSame([['setContent', '#count', false, ['__html' => '3']]], $this->parts[0]['content']['domops']);
        $this->assertArrayNotHasKey('pagelets', $this->parts[1]['content']);
        $this->assertTrue($this->parts[1]['finished']);
    }

    public function testSendsThePageletsOfAPageTransitionWhenNotStreamed(): void
    {
        $feed = new Pagelet('feed');

        $data = (new AsyncResponse())->transition("<main>$feed</main>")->getResponse();

        $this->assertSame(['feed'], array_column($data['pagelets'], 'id'));
        $this->assertTrue($data['pagelets'][0]['is_last']);
    }
}
