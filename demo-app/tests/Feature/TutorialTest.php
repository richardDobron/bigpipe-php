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
        foreach (['/tutorial/lazy-pagelets', '/tutorial/poller', '/tutorial/morph', '/tutorial/bootloader', '/tutorial/events'] as $uri) {
            $this->get($uri)->assertOk();
        }
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
        $invalid = $this->bigpipe($this->ajax('POST', '/tutorial/forms/registration', ['full_name' => '', 'email' => 'ada@example.com']));
        $valid = $this->bigpipe($this->ajax('POST', '/tutorial/forms/registration', ['full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

        // An error response, so that the FormMonitor of the form keeps the changes as unsaved.
        $this->assertSame('Check the form', $invalid['errorSummary']);
        $this->assertSame('The full name field is required.', $invalid['errorDescription']);
        $this->assertSame(['replace', '#registration'], array_slice($valid['domops'][0], 0, 2));
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
