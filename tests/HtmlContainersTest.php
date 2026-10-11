<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HtmlContainersTest extends TestCase
{
    public function testPrintsTheHtmlOfThePageletsInContainersBeforeTheScript(): void
    {
        BigPipe::setHtmlContainers(true);
        (new Pagelet('feed'))->appendContent('<p>A -- B --> C \ D</p>');

        $output = BigPipe::render();

        $this->assertStringStartsWith('<code hidden id="u_0_0"><!-- <p>A \-\- B \-\-> C \\\\ D</p> --></code>' . "\n<script>", $output);
        $this->assertStringContainsString('["setContent","#pagelet_feed",false,{"__container":"u_0_0"}]', $output);
        $this->assertStringNotContainsString('"__html"', $output);
    }

    public function testPrintsTheContainerOfAStreamedPageletBeforeItsScript(): void
    {
        BigPipe::setHtmlContainers(true);
        (new Pagelet('feed'))->appendContent('<p>Posts</p>');

        $chunks = [];
        BigPipe::stream(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertMatchesRegularExpression('/^<code hidden id="u_0_\d+"><!-- <p>Posts<\/p> --><\/code>\n<script>.*"__container":"u_0_\d+"/', $chunks[1]);
    }

    public function testKeepsTheHtmlInTheScriptByDefault(): void
    {
        (new Pagelet('feed'))->appendContent('<p>Posts</p>');

        $output = BigPipe::render();

        $this->assertStringStartsWith('<script>', $output);
        $this->assertStringContainsString('{"__html":"<p>Posts<\/p>"}', $output);
    }
}
