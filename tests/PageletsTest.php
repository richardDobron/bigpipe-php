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
        $this->assertEquals('<div id="u_0_0"></div>', (string) $pagelet);
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
}
