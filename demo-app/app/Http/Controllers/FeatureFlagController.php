<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

/**
 * A feature flag: the server chooses the module that 'tutorial/Greeting' stands for, or turns it off.
 */
class FeatureFlagController extends Controller
{
    /** The variants of the flag and the modules they choose; off is null. */
    public const VARIANTS = [
        'classic' => 'tutorial/GreetingClassic',
        'next' => 'tutorial/GreetingNext',
        'off' => null,
    ];

    public function show(Request $request)
    {
        return $this->page('tutorial.feature-flags', ['variant' => $this->variant($request)]);
    }

    /**
     * Turns the flag to another variant, for the session of the user.
     */
    public function change(Request $request, string $variant)
    {
        abort_unless(array_key_exists($variant, self::VARIANTS), 404);

        $request->session()->put('greeting_variant', $variant);

        return (new AsyncResponse())
            ->replace('#flag-switch', view('tutorial._flag-switch', ['variant' => $variant])->render())
            ->send();
    }

    /**
     * Calls 'tutorial/Greeting': the browser runs the module the server chose for it, or nothing.
     */
    public function greet(Request $request)
    {
        $variant = $this->variant($request);

        return (new AsyncResponse())
            ->defineCond('tutorial/Greeting', self::VARIANTS[$variant])
            ->call('tutorial/FlagStatus', 'show', [TransportMarker::element('greeting'), TransportMarker::element('flag-status')])
            ->call('tutorial/Greeting', 'greet', [TransportMarker::element('greeting'), 'Ada'])
            ->send();
    }

    private function variant(Request $request): string
    {
        $variant = $request->session()->get('greeting_variant');

        return array_key_exists($variant ?? '', self::VARIANTS) ? $variant : 'classic';
    }
}
