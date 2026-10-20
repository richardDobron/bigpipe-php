<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use dobron\BigPipe\TransportMarker;
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

    public function testAPageletSendsItsDisplayResourcesAndTheRest(): void
    {
        $data = (new Pagelet('feed'))
            ->addDisplayResource('feed.css')
            ->addResource('editor.css', 'feed.js')
            ->renderData();

        $this->assertSame(['feed.css'], $data['displayResources']);
        $this->assertSame(['editor.css', 'feed.js'], $data['allResources']);
        $this->assertSame(['feed.css', 'feed.js', 'editor.css'], array_keys($data['resource_map']));
    }

    public function testAPageletWithoutDisplayResourcesSendsNone(): void
    {
        $data = (new Pagelet('feed'))->renderData();

        $this->assertArrayNotHasKey('displayResources', $data);
        $this->assertArrayNotHasKey('allResources', $data);
    }

    public function testAModuleScriptKeepsItsModuleFlag(): void
    {
        Bootloader::setResourceMap([
            'chat.js' => ['type' => 'js', 'src' => '/build/chat-1a2b.js', 'module' => true],
            'chat.css' => ['type' => 'css', 'src' => '/build/chat.css', 'module' => true],
        ]);
        Bootloader::enableBootload(['Chat' => ['chat.css', 'chat.js']]);

        $data = Bootloader::dataFor([], ['require' => [['Chat', 'init']]]);

        $this->assertSame(['type' => 'js', 'src' => '/build/chat-1a2b.js', 'module' => true], $data['resource_map']['chat.js']);
        // Only a script is a module.
        $this->assertSame(['type' => 'css', 'src' => '/build/chat.css'], $data['resource_map']['chat.css']);
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

    public function testAResponseSendsTheBootloadableClassesOfItsInstances(): void
    {
        $response = new AsyncResponse();
        $response->instance('Editor')->call('open');

        $data = $response->getResponse();

        $this->assertSame(['Editor' => ['editor.css', 'editor.js']], $data['bootloadable']);
        $this->assertSame(['editor.css', 'editor.js'], array_keys($data['resource_map']));
    }

    public function testAResponseSendsTheBootloadableModulesOfTheArguments(): void
    {
        Bootloader::setResourceMap(['chat.js' => ['type' => 'js', 'src' => '/static/chat.js']]);
        Bootloader::enableBootload(['Chat' => ['chat.js']]);
        $response = new AsyncResponse();
        $chat = $response->instance('Chat');
        $response->call('Page', 'init', [['editor' => TransportMarker::module('Editor')], $chat]);

        $data = $response->getResponse();

        $this->assertSame(['Editor', 'Chat'], array_keys($data['bootloadable']));
    }

    public function testThePageEnablesItsBootloadableModulesBeforeCallingThem(): void
    {
        BigPipe::page()->call('Editor', 'open');
        new Pagelet('feed');

        $output = BigPipe::render();

        $enable = strpos($output, '["bigpipe-util\/dist\/Bootloader","enableBootload",[{"Editor":["editor.css","editor.js"]}]]');
        $this->assertNotFalse($enable);
        $this->assertStringContainsString('["bigpipe-util\/dist\/Bootloader","setResourceMap",[{"editor.css"', $output);
        $this->assertLessThan(strpos($output, '["arrive",'), $enable);
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

    public function testAResourceBringsTheResourcesItRequiresFirst(): void
    {
        Bootloader::setResourceMap([
            'vendor.js' => ['type' => 'js', 'src' => '/static/vendor.js'],
            'shared.js' => ['type' => 'js', 'src' => '/static/shared.js', 'requires' => ['vendor.js']],
            'composer.css' => ['type' => 'css', 'src' => '/static/composer.css'],
            'composer.js' => [
                'type' => 'js',
                'src' => '/static/composer.js',
                'requires' => ['shared.js', 'vendor.js', 'composer.css'],
            ],
        ]);

        $data = (new Pagelet('feed'))->addJs('composer.js')->renderData();

        $this->assertSame(['vendor.js', 'shared.js', 'composer.css', 'composer.js'], $data['js']);
        $this->assertSame(['vendor.js', 'shared.js', 'composer.css', 'composer.js'], array_keys($data['resource_map']));
        $this->assertArrayNotHasKey('requires', $data['resource_map']['composer.js']);
    }

    public function testABootloadableModuleLoadsTheResourcesItsResourcesRequire(): void
    {
        Bootloader::setResourceMap([
            'vendor.js' => ['type' => 'js', 'src' => '/static/vendor.js'],
            'editor.js' => ['type' => 'js', 'src' => '/static/editor.js', 'requires' => ['vendor.js']],
        ]);

        $data = Bootloader::dataFor([], ['require' => [['Editor', 'open']]]);

        $this->assertSame(['Editor' => ['editor.css', 'vendor.js', 'editor.js']], $data['bootloadable']);
        $this->assertEqualsCanonicalizing(['editor.css', 'vendor.js', 'editor.js'], array_keys($data['resource_map']));
    }

    public function testRequiresInACycleAreResolvedOnce(): void
    {
        Bootloader::setResourceMap([
            'a.js' => ['type' => 'js', 'src' => '/a.js', 'requires' => ['b.js']],
            'b.js' => ['type' => 'js', 'src' => '/b.js', 'requires' => ['a.js']],
        ]);

        $this->assertSame(['b.js', 'a.js'], Bootloader::resolve(['a.js', 'b.js']));
    }

    public function testResolvesOnlyTheResourcesOfAType(): void
    {
        Bootloader::setResourceMap([
            'feed.js' => ['type' => 'js', 'src' => '/feed.js', 'requires' => ['feed.css', '/fonts.css']],
        ]);

        $this->assertSame(['feed.css', '/fonts.css'], Bootloader::resolve(['feed.js'], 'css'));
    }

    public function testAnInlinePageletLinksTheStylesheetsItsStylesheetsRequire(): void
    {
        Bootloader::setResourceMap([
            'base.css' => ['type' => 'css', 'src' => '/static/base.css'],
            'feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css', 'requires' => ['base.css']],
        ]);
        BigPipe::setPipelining(false);

        $html = (string) (new Pagelet('feed'))->addCss('feed.css');

        $this->assertStringStartsWith(
            '<link rel="stylesheet" href="/static/base.css"><link rel="stylesheet" href="/static/feed.1a2b.css">',
            $html
        );
    }

    public function testRejectsRequiresThatAreNotNames(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        Bootloader::setResourceMap(['feed.js' => ['type' => 'js', 'src' => '/feed.js', 'requires' => [1]]]);
    }

    public function testReadsTheViteManifest(): void
    {
        Bootloader::reset();
        $path = tempnam(sys_get_temp_dir(), 'manifest');
        file_put_contents($path, json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-1a2b.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
                'imports' => ['_shared-3c4d.js'],
                'css' => ['assets/app-5e6f.css'],
            ],
            '_shared-3c4d.js' => ['file' => 'assets/shared-3c4d.js', 'css' => ['assets/shared-7a8b.css']],
            'resources/js/Editor.js' => [
                'file' => 'assets/Editor-9c0d.js',
                'isDynamicEntry' => true,
                'imports' => ['_shared-3c4d.js', 'resources/images/logo.png'],
            ],
            'resources/css/app.css' => ['file' => 'assets/app-e1f2.css', 'isEntry' => true],
            'resources/images/logo.png' => ['file' => 'assets/logo-3a4b.png'],
        ]));

        Bootloader::fromViteManifest($path, '/build');
        unlink($path);
        Bootloader::enableBootload(['Editor' => ['resources/js/Editor.js']]);

        $data = Bootloader::dataFor([], ['require' => [['Editor', 'open']]]);

        $this->assertSame(
            ['Editor' => ['assets/shared-7a8b.css', '_shared-3c4d.js', 'resources/js/Editor.js']],
            $data['bootloadable']
        );
        $this->assertSame(
            ['type' => 'js', 'src' => '/build/assets/Editor-9c0d.js', 'module' => true],
            $data['resource_map']['resources/js/Editor.js']
        );
        $this->assertSame(['type' => 'css', 'src' => '/build/assets/shared-7a8b.css'], $data['resource_map']['assets/shared-7a8b.css']);
        $this->assertSame('/build/assets/app-e1f2.css', Bootloader::src('resources/css/app.css'));
        $this->assertSame('resources/images/logo.png', Bootloader::src('resources/images/logo.png'));
        $this->assertSame(
            ['assets/shared-7a8b.css', '_shared-3c4d.js', 'assets/app-5e6f.css', 'resources/js/app.js'],
            Bootloader::resolve(['resources/js/app.js'])
        );
    }

    public function testRejectsAMissingViteManifest(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        Bootloader::fromViteManifest(__DIR__ . '/missing-manifest.json');
    }

    public function testUsesTheViteDevServerWhileItRuns(): void
    {
        Bootloader::reset();
        $hot = tempnam(sys_get_temp_dir(), 'hot');
        file_put_contents($hot, "http://[::1]:5173\n");

        Bootloader::fromViteManifest(__DIR__ . '/missing-manifest.json', '/build/', $hot);
        unlink($hot);
        Bootloader::enableBootload(['Editor' => ['resources/js/Editor.ts', 'resources/css/editor.scss', '/js/legacy.js']]);

        $data = Bootloader::dataFor([], ['require' => [['Editor', 'open']]]);

        $this->assertSame([
            'resources/js/Editor.ts' => ['type' => 'js', 'src' => 'http://[::1]:5173/resources/js/Editor.ts', 'module' => true],
            'resources/css/editor.scss' => ['type' => 'css', 'src' => 'http://[::1]:5173/resources/css/editor.scss'],
        ], $data['resource_map']);
        $this->assertSame('http://[::1]:5173/resources/js/app.js', Bootloader::src('resources/js/app.js'));
        $this->assertSame('/js/legacy.js', Bootloader::src('/js/legacy.js'));
    }

    public function testReadsTheManifestWithoutTheHotFile(): void
    {
        Bootloader::reset();
        $path = tempnam(sys_get_temp_dir(), 'manifest');
        file_put_contents($path, json_encode(['resources/js/app.js' => ['file' => 'assets/app-1a2b.js']]));

        Bootloader::fromViteManifest($path, '/build/', __DIR__ . '/missing-hot');
        unlink($path);

        $this->assertSame('/build/assets/app-1a2b.js', Bootloader::src('resources/js/app.js'));
        $this->assertSame('resources/js/other.js', Bootloader::src('resources/js/other.js'));
    }
}
