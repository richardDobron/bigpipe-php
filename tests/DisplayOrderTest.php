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
        preg_match_all('/onPageletArrive\(\{"id":"([^"]+)"/', $output, $matches);

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
}
