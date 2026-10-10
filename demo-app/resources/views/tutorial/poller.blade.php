@extends('layout')

@section('header', 'Poller')

@section('description')
    A poller requests a URL again and again and applies every response, like any other. The server is in control:
    each request sends the id of the poller, so a response can change its interval, mute it or stop it.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>new Poller($url, $interval)</h3><p>Starts with the page, or with the pagelet whose content prints it.</p></div></li>
        <li><div><h3>Poller::requestedId()</h3><p>The poller that sent the request, to control it from the response.</p></div></li>
        <li><div><h3>setInterval, mute, stop</h3><p>Called on the poller by the response.</p></div></li>
        <li><div><h3>setMaxRequests(), setMuteWhenIdle()</h3><p>Limits, and a pause while the user is away or the tab is hidden.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
// The pagelet of the deploy starts the poller with itself
(new Poller(route('deploy.status'), 2000))
    ->setMaxRequests(20)
    ->start();

// The endpoint of the poller
$response = (new AsyncResponse())
    ->setContent('#deploy-progress', view('progress', compact('progress'))->render());

if ($progress >= 100) {
    $response->call(Poller::requestedId(), 'stop');
}

return $response->send();
CODE
)

@section('examples')
    <x-example title="A deploy that reports its progress" caption="Start it: the response is a pagelet that starts a poller. Every 2 s the poller asks for the progress, and the server stops it when the deploy is done.">
        <div class="surface">
            <div id="deploy">
                <div class="deploy-idle">
                    <p>main · 4 steps · about {{ \App\Pagelets\Tutorial\DeployPagelet::SECONDS }} s</p>
                    <a href="{{ url()->current() }}" ajaxify="{{ route('poller.deploy') }}" rel="async-post" class="btn sm">Deploy</a>
                </div>
            </div>
        </div>
    </x-example>
@endsection
