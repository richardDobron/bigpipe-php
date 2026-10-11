<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @runTestsInSeparateProcesses
 */
class PageletsTest extends TestCase
{
    use MatchesSnapshots;

    public function testHtmlResponse(): void
    {
        $bigPipe = new BigPipe();

        $bigPipe->require("require('Page').setup()");

        $pagelet = new Pagelet('content');

        $pagelet
            ->appendContent(__DIR__ . '/pagelets/header.php', true)
            ->appendContent('Main page content')
            ->appendContent(__DIR__ . '/pagelets/footer.php', true)
            ->addCss('./styles/content.css')
            ->addJs("./scripts/app.js")
            ->require("require('Users').setup()");

        $this->assertMatchesSnapshot((string) $bigPipe);
        $this->assertEquals('<div id="pagelet_content"></div>', (string) $pagelet);
    }

    public function testRendersPageletsCreatedWhileRenderingAnother(): void
    {
        (new Pagelet('content'))->defer(function () {
            $sidebar = (new Pagelet('sidebar'))->appendContent('friends');

            return "<aside>$sidebar</aside>";
        });

        $output = BigPipe::render();

        preg_match_all('/\["arrive",\{"id":"([^"]+)"/', $output, $matches);
        $this->assertSame(['content', 'sidebar'], $matches[1]);
        $this->assertMatchesRegularExpression('/"id":"sidebar".*"is_last":true/', $output);
    }

    public function testOnAfterLoad(): void
    {
        $pagelet = new Pagelet('content');
        $this->assertArrayNotHasKey('onafterload', $pagelet->renderData());

        $pagelet
            ->onAfterLoad(['Prefetch', 'start'], ['/next'])
            ->onAfterLoad("require('Analytics').track()");

        $this->assertSame([
            'require' => [
                ['Prefetch', 'start', ['/next']],
                ['Analytics', 'track'],
            ],
        ], $pagelet->renderData()['onafterload']);
        $this->assertSame([], $pagelet->jsmods()['require']);

        $this->expectException(BigPipeInvalidArgumentException::class);
        $pagelet->onAfterLoad('Analytics.track()');
    }

    public function testProxyRequire(): void
    {
        $pagelet = new Pagelet('content');

        $pagelet->require()->Users()->setup(['abc']);
        $pagelet->require(priority: -1)->Page()->init();

        $this->assertSame([
            ['Page', 'init'],
            ['Users', 'setup', ['abc']],
        ], $pagelet->jsmods()['require']);
    }

    public function testCallsOfTheSamePriorityKeepTheirOrder(): void
    {
        $pagelet = new Pagelet('content');

        $pagelet->call('Alfa', 'init', [], 5);
        $pagelet->call('Bravo', 'init', [], 5);
        $pagelet->call('Charlie', 'init', [], 1);

        $this->assertSame([
            ['Charlie', 'init'],
            ['Alfa', 'init'],
            ['Bravo', 'init'],
        ], $pagelet->jsmods()['require']);
    }
}
