<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TutorialTest extends TestCase
{
    protected function tearDown(): void
    {
        // The superglobals that ajax() fills, e.g. with the parameter of a page transition.
        $_GET = $_REQUEST = [];

        parent::tearDown();
    }

    /**
     * A response of BigPipe is JSON behind the for (;;); shield.
     */
    private function bigpipe(TestResponse $response): array
    {
        return json_decode(substr($response->getContent(), strlen('for (;;);')), true);
    }

    private function ajax(string $method, string $uri, array $data = []): TestResponse
    {
        // BigPipe reads the query string from the superglobals, which the test client does not fill.
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $_GET = $_REQUEST = $query;

        return $this->call($method, $uri, $data, [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    }

    public function test_the_tutorials_render(): void
    {
        foreach (['/tutorial/lazy-pagelets', '/tutorial/poller', '/tutorial/morph', '/tutorial/bootloader', '/tutorial/events', '/tutorial/feature-flags', '/tutorial/layers'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_the_landing_page_lists_the_new_tutorials(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/tutorial/feature-flags"', false)->assertSee('href="/tutorial/layers"', false);
    }

    public function test_the_landing_page_and_a_tutorial_answer_a_page_transition(): void
    {
        $home = $this->bigpipe($this->ajax('GET', '/?quickling[version]='));
        $tutorial = $this->bigpipe($this->ajax('GET', '/tutorial/dialogs?quickling[version]='));

        $this->assertSame('BigPipe: pipelining web pages', $home['payload']['title']);
        $this->assertSame('Dialogs · BigPipe', $tutorial['payload']['title']);
        // The canvas of the tutorial, and its modules; the layout and Quickling stay as they are.
        $this->assertStringContainsString('id="inspector-log"', $tutorial['domops'][0][3]['__html']);
        $this->assertStringNotContainsString('class="topbar"', $tutorial['domops'][0][3]['__html']);
        $this->assertSame(['tutorial/Code', 'tutorial/Inspector'], array_column($tutorial['jsmods']['require'], 0));
    }

    public function test_the_pagelets_are_streamed_with_a_fallback(): void
    {
        $html = $this->get('/tutorial/pagelets')->streamedContent();

        $this->assertStringContainsString('<div id="pagelet_feed"></div>', $html);
        $this->assertStringContainsString('"id":"suggestions"', $html);
        // The ads pagelet throws: its fallback is sent instead.
        $this->assertStringContainsString('this is the fallback of the pagelet', $html);
    }

    public function test_the_html_of_the_pagelets_is_in_containers_when_they_are_on(): void
    {
        $json = $this->get('/tutorial/pagelets')->streamedContent();
        $containers = $this->get('/tutorial/pagelets?containers=1')->streamedContent();

        $this->assertStringNotContainsString('<code hidden id=', $json);
        // The HTML is in a hidden container, and the script of the pagelet only names it.
        $this->assertMatchesRegularExpression('/<code hidden id="[^"]+"><!-- /', $containers);
        $this->assertStringContainsString('{"__container":', $containers);
    }

    public function test_a_pagelet_is_refreshed(): void
    {
        $data = $this->bigpipe($this->ajax('POST', '/tutorial/pagelets/refresh/profile'));

        $this->assertSame('profile', $data['pagelets'][0]['id']);
    }

    public function test_a_lazy_pagelet_answers_with_the_pagelet(): void
    {
        $data = $this->bigpipe($this->ajax('GET', '/tutorial/lazy-pagelets/load/comments'));

        $this->assertSame('comments', $data['pagelets'][0]['id']);
    }

    public function test_the_poller_is_stopped_when_the_deploy_is_done(): void
    {
        $deploy = $this->bigpipe($this->ajax('POST', '/tutorial/poller/deploy'));
        $this->assertSame('bigpipe-util/dist/Poller', $deploy['pagelets'][0]['jsmods']['require'][0][0]);

        $this->withSession(['deploy_started_at' => microtime(true) - 60]);
        $data = $this->bigpipe($this->ajax('GET', '/tutorial/poller/status?__poller=poller_test'));

        $this->assertContains(['poller_test', 'stop'], array_map(fn (array $call) => array_slice($call, 0, 2), $data['jsmods']['require']));
    }

    public function test_the_editor_is_bootloaded_with_its_files(): void
    {
        $data = $this->bigpipe($this->ajax('POST', '/tutorial/bootloader/open'));

        $this->assertSame(['editor.css', 'editor.js'], $data['bootloadable']['Editor']);
        $this->assertStringEndsWith('/bootloaded/editor.js', $data['resource_map']['editor.js']['src']);
        $this->assertSame(['Editor', 'open'], array_slice($data['jsmods']['require'][0], 0, 2));
    }

    public function test_an_order_is_informed_to_the_widgets(): void
    {
        $this->get('/tutorial/events')->assertOk();

        $this->bigpipe($this->ajax('POST', '/tutorial/events/order'));
        $data = $this->bigpipe($this->ajax('POST', '/tutorial/events/order'));

        [$module, $method, [$event, $order]] = $data['jsmods']['require'][0];
        $this->assertSame(['bigpipe-util/dist/core/Arbiter', 'informState', 'ORDER/PLACED'], [$module, $method, $event]);
        $this->assertSame(2, $order['count']);
        $this->assertSame(108.5, $order['total']);
    }

    public function test_an_invalid_form_is_an_error_and_a_valid_one_is_replaced(): void
    {
        $invalidResponse = $this->ajax('POST', '/tutorial/forms/registration', ['full_name' => '', 'email' => 'ada@example.com']);
        $invalid = $this->bigpipe($invalidResponse->assertStatus(422));
        $valid = $this->bigpipe($this->ajax('POST', '/tutorial/forms/registration', ['full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

        // An error response, so that the FormMonitor of the form keeps the changes as unsaved.
        $this->assertSame('Check the form', $invalid['errorSummary']);
        $this->assertSame('The full name field is required.', $invalid['errorDescription']);
        // The messages next to the fields, applied by the browser despite the status.
        $this->assertSame(['setContent', '#error-full_name'], array_slice($invalid['domops'][0], 0, 2));
        $this->assertSame('The full name field is required.', $invalid['domops'][0][3]['__html']);
        $this->assertSame(['setContent', '#error-email'], array_slice($invalid['domops'][1], 0, 2));
        $this->assertSame('', $invalid['domops'][1][3]['__html']);
        $this->assertSame(['replace', '#registration'], array_slice($valid['domops'][0], 0, 2));
    }

    public function test_a_redirect_replaces_the_history_or_keeps_the_layout(): void
    {
        $this->get('/tutorial/redirecting')->assertOk();

        $plain = $this->bigpipe($this->ajax('POST', '/tutorial/redirecting/redirect'));
        $replace = $this->bigpipe($this->ajax('POST', '/tutorial/redirecting/redirect-replace'));
        $transition = $this->bigpipe($this->ajax('POST', '/tutorial/redirecting/redirect-transition'));

        // ServerRedirect.redirectPageTo(url, delay, replace, transition)
        $this->assertSame(['bigpipe-util/dist/core/ServerRedirect', 'redirectPageTo', ['/', 0]], array_slice($plain['jsmods']['require'][0], 0, 3));
        $this->assertSame(['/', 0, true, false], $replace['jsmods']['require'][0][2]);
        $this->assertSame(['/', 0, false, true], $transition['jsmods']['require'][0][2]);
    }

    public function test_the_server_chooses_the_variant_of_the_greeting(): void
    {
        $this->get('/tutorial/feature-flags')->assertOk()->assertSee('tutorial/Greeting');

        $change = $this->bigpipe($this->ajax('POST', '/tutorial/feature-flags/variant/next'));
        $this->assertSame(['replace', '#flag-switch'], array_slice($change['domops'][0], 0, 2));
        $this->assertSame('next', session('greeting_variant'));
        $this->ajax('POST', '/tutorial/feature-flags/variant/beta')->assertNotFound();

        // The define of tutorial/Greeting, next to the CSRF token of the page.
        $chosen = fn (array $data) => array_column($data['jsmods']['define'], 1, 0)['tutorial/Greeting'];

        $next = $this->bigpipe($this->ajax('POST', '/tutorial/feature-flags/greet'));
        $this->assertSame(['__rc' => 'tutorial/GreetingNext'], $chosen($next));
        $this->assertSame(['tutorial/FlagStatus', 'tutorial/Greeting'], array_column($next['jsmods']['require'], 0));

        // Turned off: the module stands for null, and its call does nothing in the browser.
        $this->withSession(['greeting_variant' => 'off']);
        $off = $this->bigpipe($this->ajax('POST', '/tutorial/feature-flags/greet'));
        $this->assertSame(['__rc' => null], $chosen($off));
    }

    public function test_the_server_opens_a_menu_next_to_the_button(): void
    {
        $this->get('/tutorial/layers')->assertOk();

        $data = $this->bigpipe($this->ajax('POST', '/tutorial/layers/menu/below'));

        [[$id, $module, [$html, $options]]] = $data['jsmods']['instances'];
        $this->assertSame('bigpipe-util/dist/core/layer/ContextualLayer', $module);
        $this->assertStringContainsString('role="menu"', $html);
        // Placed next to the element that sent the request, with the behaviors as modules.
        $this->assertSame(['__rel' => 1], $options['context']);
        $this->assertSame('below', $options['position']);
        $this->assertContains(['__m' => 'bigpipe-util/dist/core/layer/HideOnEscape'], $options['behaviors']);
        // ->call('show') on the instance.
        $this->assertSame([$id, 'show'], array_slice($data['jsmods']['require'][0], 0, 2));

        $this->ajax('POST', '/tutorial/layers/menu/above')->assertNotFound();

        $pick = $this->bigpipe($this->ajax('POST', '/tutorial/layers/pick/rename'));
        $this->assertSame(['Toastr', 'success'], array_slice($pick['jsmods']['require'][0], 0, 2));
    }

    public function test_a_dialog_is_opened_with_its_behaviors_changed(): void
    {
        $this->get('/tutorial/dialogs')->assertOk()->assertSee('/tutorial/dialogs/behavior/static', false);

        $options = fn (string $behavior) => $this->bigpipe($this->ajax('POST', '/tutorial/dialogs/behavior/'.$behavior))['jsmods']['require'][0][2][0];

        $static = $options('static');
        $this->assertSame('static', $static['backdrop']);
        $this->assertFalse($static['keyboard']);
        $this->assertTrue($options('hide-on-success')['hideOnSuccess']);
        $this->assertSame(['centered' => true], $options('centered')['position']);
        $this->assertSame(1000, $options('delayed')['timeout']);
        $free = $options('free-focus');
        $this->assertFalse($free['autoFocus'] || $free['trapFocus'] || $free['refocus']);

        $this->ajax('POST', '/tutorial/dialogs/behavior/unknown')->assertNotFound();

        $save = $this->bigpipe($this->ajax('POST', '/tutorial/dialogs/behavior-save'));
        $this->assertSame(['Toastr', 'success'], array_slice($save['jsmods']['require'][0], 0, 2));
    }

    public function test_the_quote_is_morphed_or_replaced(): void
    {
        $morph = $this->bigpipe($this->ajax('POST', '/tutorial/morph/quote', ['seats' => '70', 'plan' => 'team']));
        $replace = $this->bigpipe($this->ajax('POST', '/tutorial/morph/quote', ['seats' => '2', 'mode' => 'replace']));

        $this->assertSame(['morph', '#quote'], array_slice($morph['domops'][0], 0, 2));
        $this->assertStringContainsString('More than 50 seats', $morph['domops'][0][3]['__html']);
        $this->assertSame(['replace', '#quote'], array_slice($replace['domops'][0], 0, 2));
        $this->assertStringContainsString('$12.00', $replace['domops'][0][3]['__html']);
    }
}
