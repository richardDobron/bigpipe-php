<?php

declare(strict_types=1);

namespace DisplayOrderTest;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

class AdsPagelet extends Pagelet
{
    protected int $phase = 1;
}

/**
 * @runTestsInSeparateProcesses
 */
class DisplayOrderTest extends TestCase
{
    private static function pageletIds(string $output): array
    {
        preg_match_all('/\["arrive",\{"id":"([^"]+)"/', $output, $matches);

        return $matches[1];
    }

    public function testSendsThePhaseAndTheDisplayDependency(): void
    {
        $feed = new Pagelet('feed');
        $chat = (new Pagelet('chat'))->setPhase(2)->displayAfter($feed, 'sidebar', 'feed');

        $this->assertArrayNotHasKey('phase', $feed->renderData());
        $this->assertArrayNotHasKey('display_dependency', $feed->renderData());
        $this->assertSame(2, $chat->renderData()['phase']);
        $this->assertSame(['feed', 'sidebar'], $chat->renderData()['display_dependency']);
    }

    public function testDisplaysAPageletCreatedWhileRenderingAnotherAfterIt(): void
    {
        (new Pagelet('content'))->defer(function () {
            $sidebar = (new Pagelet('sidebar'))->appendContent('friends');

            return "<aside>$sidebar</aside>";
        });

        $output = BigPipe::render();

        $this->assertMatchesRegularExpression('/"id":"sidebar".*"display_dependency":\["content"\]/', $output);
    }

    public function testAPageletCreatedOutsideOfAnotherHasNoDependency(): void
    {
        $this->assertArrayNotHasKey('display_dependency', (new Pagelet('feed'))->renderData());
    }

    public function testAPageletClassCanDeclareItsPhase(): void
    {
        $this->assertSame(1, (new AdsPagelet())->getPhase());
    }

    public function testRendersThePageletsInTheOrderOfTheirPhases(): void
    {
        new AdsPagelet();
        (new Pagelet('chat'))->setPhase(1);
        new Pagelet('feed');
        (new Pagelet('header'))->setPhase(-1);

        $output = BigPipe::render();

        $this->assertSame(['header', 'feed', 'ads', 'chat'], self::pageletIds($output));
        $this->assertStringContainsString('"id":"chat","js":[],"css":[]', $output);
        $this->assertMatchesRegularExpression('/"id":"chat".*"is_last":true/', $output);
    }

    public function testStreamsThePageletsInTheOrderOfTheirPhases(): void
    {
        new AdsPagelet();
        (new Pagelet('content'))->defer(function () {
            new Pagelet('comments');

            return '<div id="pagelet_comments"></div>';
        });

        $chunks = [];
        BigPipe::stream(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertSame(['content', 'comments', 'ads', BigPipe::LAST_PAGELET_ID], self::pageletIds(implode('', $chunks)));
    }

    public function testSendsThePageletsOfAResponseInTheOrderOfTheirPhases(): void
    {
        $response = (new AsyncResponse())
            ->pagelet(new AdsPagelet(), '#ads')
            ->pagelet(new Pagelet('feed'), '#feed');

        $this->assertSame(['feed', 'ads'], array_column($response->getResponse()['pagelets'], 'id'));
    }

    public function testRejectsAnInvalidDependency(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new Pagelet('chat'))->displayAfter('not valid');
    }

    /**
     * @return list<string> the ids of the pagelets marked as the last of their phase
     */
    private static function lastInPhase(string $output): array
    {
        preg_match_all('/\["arrive",(\{.*?\})\]\);(?=<\/script>|\r?\n|\(window)/', $output, $matches);

        $ids = [];
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!empty($data['last_in_phase'])) {
                $ids[] = $data['id'];
            }
        }

        return $ids;
    }

    public function testMarksTheLastPageletOfEveryPhase(): void
    {
        (new Pagelet('chat'))->setPhase(1);
        new Pagelet('feed');
        (new Pagelet('ads'))->setPhase(1);
        new Pagelet('header');

        $this->assertSame(['header', 'ads'], self::lastInPhase(BigPipe::render()));
    }

    public function testMarksTheLastPageletOfEveryPhaseWhileStreaming(): void
    {
        (new Pagelet('content'))->defer(function () {
            $sidebar = new Pagelet('sidebar');

            return "<aside>$sidebar</aside>";
        });
        (new Pagelet('chat'))->setPhase(1);

        $chunks = '';
        BigPipe::stream(function (string $chunk) use (&$chunks): void {
            $chunks .= $chunk;
        });

        $this->assertSame(['content', 'sidebar', 'chat', BigPipe::LAST_PAGELET_ID], self::pageletIds($chunks));
        $this->assertSame(['sidebar', 'chat'], self::lastInPhase($chunks));
    }

    public function testMarksTheLastPageletOfEveryPhaseOfAResponse(): void
    {
        $response = (new AsyncResponse())->transition('<main>' . (new Pagelet('feed')) . (new Pagelet('ads'))->setPhase(1) . '</main>');
        $pagelets = $response->getResponse()['pagelets'];

        $this->assertSame([true, true], array_column($pagelets, 'last_in_phase'));

        $chunks = '';
        $streamed = (new AsyncResponse())->transition('<main>' . (new Pagelet('feed')) . (new Pagelet('news')) . '</main>');
        $streamed->stream(function (string $part) use (&$chunks): void {
            $chunks .= $part;
        });
        $last = [];
        foreach (array_filter(explode(AsyncResponse::STREAM_DELIMITER, $chunks)) as $part) {
            foreach (json_decode($part, true, 512, JSON_THROW_ON_ERROR)['content']['pagelets'] ?? [] as $data) {
                if (!empty($data['last_in_phase'])) {
                    $last[] = $data['id'];
                }
            }
        }

        $this->assertSame(['news'], $last);
    }
}
