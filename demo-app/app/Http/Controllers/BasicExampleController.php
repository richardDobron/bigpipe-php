<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Http\Request;

class BasicExampleController extends Controller
{
    public function statsPanel()
    {
        return (new AsyncResponse())
            ->setContent('#box-stats', view('partials.stats')->render())
            ->send();
    }

    public function showPhoneNumber(Request $request)
    {
        $phoneNumbers = [
            1 => '+4131359771081',
            2 => '+4219104783211',
        ];

        return (new AsyncResponse())
            ->replace('', '<span class="number">'.e($phoneNumbers[$request->get('id')]).'</span>')
            ->send();
    }

    public function loadImage()
    {
        return (new AsyncResponse())
            ->call('tutorial/Image', 'set', [
                'https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg',
            ])
            ->send();
    }
}
