<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;

class DomReferencesController extends Controller
{
    public function highlight(string $id)
    {
        abort_unless(in_array($id, ['box-a', 'box-b', 'box-c'], true), 404);

        // The element is a module of its own: any module can require 'tutorial/Target' by name.
        return (new AsyncResponse())
            ->defineElement('tutorial/Target', $id)
            ->call('tutorial/Highlight', 'pulse')
            ->send();
    }
}
