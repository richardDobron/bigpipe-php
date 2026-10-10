<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function show()
    {
        // Sent with the page: a module of its own that the JavaScript of the page requires by name.
        BigPipe::page()->define('AppConfig', $this->config('en'));

        return $this->page('tutorial.configuration');
    }

    public function change(Request $request)
    {
        $locale = $request->input('locale') === 'sk' ? 'sk' : 'en';

        // Defined again, it replaces the previous one: the next read sees the new values.
        return (new AsyncResponse())
            ->define('AppConfig', $this->config($locale))
            ->call('tutorial/ConfigReader', 'show', [TransportMarker::element('config-output')])
            ->send();
    }

    private function config(string $locale): array
    {
        return [
            'locale' => $locale,
            'greeting' => $locale === 'sk' ? 'Ahoj, svet!' : 'Hello, world!',
            'request' => ['timeout' => 8000, 'retries' => 2],
        ];
    }
}
