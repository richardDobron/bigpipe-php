<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\TransportMarker;

class BootloaderController extends Controller
{
    public function open()
    {
        // Usually set once, e.g. from the manifest of the bundler. A response sends only what its calls need.
        Bootloader::setResourceMap([
            'editor.css' => ['type' => 'css', 'src' => asset('bootloaded/editor.css')],
            'editor.js' => ['type' => 'js', 'src' => asset('bootloaded/editor.js')],
        ]);
        Bootloader::enableBootload(['Editor' => ['editor.css', 'editor.js']]);

        return (new AsyncResponse())
            ->call('Editor', 'open', [TransportMarker::element('draft')])
            ->send();
    }
}
