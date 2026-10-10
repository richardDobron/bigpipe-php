<?php

declare(strict_types=1);

use dobron\BigPipe\Bootloader;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use dobron\BigPipe\Pagelet;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PageletJsTest extends TestCase
{
    public function testSendsTheModulesToRunOnceTheJsFilesAreLoaded(): void
    {
        $data = (new Pagelet('sidebar'))
            ->addJs('/js/sidebar.js')
            ->call('Tooltips', 'init')
            ->onLoad(['Sidebar', 'init'], ['compact' => true])
            ->renderData();

        $this->assertSame([['Tooltips', 'init']], $data['jsmods']['require']);
        $this->assertSame(['require' => [['Sidebar', 'init', ['compact' => true]]]], $data['onload']);
        $this->assertArrayNotHasKey('jsNonBlock', $data);
    }

    public function testSendsNoOnloadModulesWithoutAny(): void
    {
        $this->assertArrayNotHasKey('onload', (new Pagelet('sidebar'))->renderData());
    }

    public function testLoadsTheJsFilesRightAfterTheDisplay(): void
    {
        $this->assertTrue((new Pagelet('chat'))->setJSNonBlock()->renderData()['jsNonBlock']);
    }

    public function testSendsTheBootloadableModulesOfTheOnloadModules(): void
    {
        Bootloader::enableBootload(['Editor' => ['/js/editor.js']]);

        $data = (new Pagelet('composer'))->onLoad(['Editor', 'open'])->renderData();

        $this->assertSame(['Editor' => ['/js/editor.js']], $data['bootloadable']);
    }

    public function testRejectsAnInvalidCall(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new Pagelet('sidebar'))->onLoad([]);
    }
}
