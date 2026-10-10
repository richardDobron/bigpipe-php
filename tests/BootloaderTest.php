<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BootloaderTest extends TestCase
{
    protected function setUp(): void
    {
        Bootloader::setResourceMap([
            'feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css'],
            'feed.js' => ['type' => 'js', 'src' => '/static/feed.3c4d.js'],
            'editor.css' => ['type' => 'css', 'src' => '/static/editor.css'],
            'editor.js' => ['type' => 'js', 'src' => '/static/editor.js'],
        ]);
        Bootloader::enableBootload(['Editor' => ['editor.css', 'editor.js']]);
    }

    public function testRejectsAResourceWithoutATypeOrSrc(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        Bootloader::setResourceMap(['feed.css' => ['src' => '/feed.css']]);
    }

    public function testAPageletSendsTheResourcesItUses(): void
    {
        $data = (new Pagelet('feed'))->addCss('feed.css')->addJs('/js/other.js')->renderData();

        $this->assertSame(['feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css']], $data['resource_map']);
        $this->assertArrayNotHasKey('bootloadable', $data);
    }

    public function testAPageletSendsTheBootloadableModulesItCalls(): void
    {
        $data = (new Pagelet('composer'))->call('Editor', 'open')->renderData();

        $this->assertSame(['Editor' => ['editor.css', 'editor.js']], $data['bootloadable']);
        $this->assertSame(['editor.css', 'editor.js'], array_keys($data['resource_map']));
    }

    public function testAPageletWithoutResourcesSendsNoMap(): void
    {
        $data = (new Pagelet('plain'))->call('Plain', 'init')->renderData();

        $this->assertArrayNotHasKey('resource_map', $data);
        $this->assertArrayNotHasKey('bootloadable', $data);
    }

    public function testAResponseSendsTheBootloadableModulesItCalls(): void
    {
        $response = (new AsyncResponse())->call('Editor', 'open')->getResponse();

        $this->assertSame(['Editor' => ['editor.css', 'editor.js']], $response['bootloadable']);
        $this->assertSame(['editor.css', 'editor.js'], array_keys($response['resource_map']));
    }

    public function testThePageEnablesItsBootloadableModulesBeforeCallingThem(): void
    {
        BigPipe::page()->call('Editor', 'open');
        new Pagelet('feed');

        $output = BigPipe::render();

        $enable = strpos($output, '["bigpipe-util\/dist\/Bootloader","enableBootload",[{"Editor":["editor.css","editor.js"]}]]');
        $this->assertNotFalse($enable);
        $this->assertStringContainsString('["bigpipe-util\/dist\/Bootloader","setResourceMap",[{"editor.css"', $output);
        $this->assertLessThan(strpos($output, 'onPageletArrive'), $enable);
        $this->assertLessThan(strpos($output, '["Editor","open"]'), $enable);
    }

    public function testThePageSendsTheModulesToPreload(): void
    {
        Bootloader::setResourceMap(['dialog.js' => ['type' => 'js', 'src' => '/static/dialog.js']]);
        Bootloader::enableBootload(['Dialog' => ['dialog.js']], 2);
        new Pagelet('feed');

        $output = BigPipe::render();

        $this->assertStringContainsString(
            '["bigpipe-util\/dist\/Bootloader","enableBootload",[{"Dialog":{"resources":["dialog.js"],"priority":2}}]]',
            $output
        );
        $this->assertStringNotContainsString('"Editor"', $output);
    }

    public function testPreloadsModulesOnThePage(): void
    {
        Bootloader::preloadModules('Editor');

        $output = BigPipe::render();

        $this->assertStringContainsString('["bigpipe-util\/dist\/Bootloader","enableBootload",[{"Editor":["editor.css","editor.js"]}]]', $output);
        $this->assertStringContainsString('["bigpipe-util\/dist\/Bootloader","preloadModules",[["Editor"]]]', $output);
    }

    public function testAPageletSendsWhatItPrefetches(): void
    {
        $data = (new Pagelet('feed'))
            ->prefetch('editor.js')
            ->prefetchCall('Editor', 'warmUp')
            ->renderData();

        $this->assertSame(['editor.js'], $data['prefetchRsrcs']);
        $this->assertSame(['require' => [['Editor', 'warmUp']]], $data['prefetchJsmods']);
        $this->assertSame(['Editor' => ['editor.css', 'editor.js']], $data['bootloadable']);
        $this->assertSame(['editor.css', 'editor.js'], array_keys($data['resource_map']));
    }
}
