<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;

/**
 * A menu the server creates: a ContextualLayer with its behaviors, next to the button that asked for it.
 */
class LayerController extends Controller
{
    public const ITEMS = ['rename' => 'Rename', 'duplicate' => 'Duplicate', 'archive' => 'Archive'];

    public function menu(string $position)
    {
        abort_unless(in_array($position, ['below', 'right'], true), 404);

        $response = new AsyncResponse();

        $response
            ->instance('bigpipe-util/dist/core/layer/ContextualLayer', [
                view('tutorial._layer-menu', ['items' => self::ITEMS])->render(),
                [
                    // The element that sent the request: the button that was clicked.
                    'context' => TransportMarker::relative(),
                    'position' => $position,
                    'alignment' => 'start',
                    'offsetX' => $position === 'right' ? 6 : 0,
                    'offsetY' => $position === 'below' ? 6 : 0,
                    // A request sent from the menu that succeeds hides it.
                    'hideOnSuccess' => true,
                    'behaviors' => [
                        TransportMarker::module('bigpipe-util/dist/core/layer/HideOnBlur'),
                        TransportMarker::module('bigpipe-util/dist/core/layer/HideOnEscape'),
                        TransportMarker::module('bigpipe-util/dist/core/layer/HideOnSuccess'),
                        TransportMarker::module('bigpipe-util/dist/core/layer/DestroyOnHide'),
                    ],
                ],
            ])
            ->call('show');

        return $response->send();
    }

    public function pick(string $item)
    {
        abort_unless(array_key_exists($item, self::ITEMS), 404);

        return (new AsyncResponse())
            ->call('Toastr', 'success', [self::ITEMS[$item].': the server got the choice, and the menu hid itself.'])
            ->send();
    }
}
