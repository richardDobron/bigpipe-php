<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;

class TransportController extends Controller
{
    public function collection()
    {
        return (new AsyncResponse())
            ->call('tutorial/Collections', 'setup', [
                TransportMarker::element('data-box'),
                TransportMarker::map([
                    ['Jack', 20],
                    ['Alan', 34],
                    ['Bill', 10],
                    ['Sam', 9],
                ]),
                TransportMarker::set(['a', 'b', 'c', 'c', 'c']),
            ])
            ->send();
    }
}
