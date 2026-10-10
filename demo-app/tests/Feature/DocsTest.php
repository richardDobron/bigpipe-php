<?php

namespace Tests\Feature;

use App\Docs\Docs;
use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_the_index_leads_to_the_first_page(): void
    {
        $this->get('/docs')->assertRedirect('/docs/getting_started');
    }

    public function test_every_page_of_the_sidebar_renders(): void
    {
        foreach (app(Docs::class)->slugs() as $slug) {
            $this->get("/docs/{$slug}")->assertOk()->assertSee('docs-nav', false);
        }
    }

    public function test_an_unknown_page_is_not_found(): void
    {
        $this->get('/docs/nope')->assertNotFound();
        $this->get('/docs/..%2F..%2F.env')->assertNotFound();
    }

    public function test_the_page_has_its_title_sidebar_and_table_of_contents(): void
    {
        $this->get('/docs/pagelets')
            ->assertOk()
            ->assertSee('Pagelets API')
            ->assertSee('Parallel rendering')
            ->assertSee('docs-toc', false)
            ->assertSee('id="streaming"', false);
    }

    public function test_code_blocks_have_their_language_and_title(): void
    {
        $this->get('/docs/getting_started')
            ->assertSee('class="language-javascript"', false)
            ->assertSee('<span>resources/js/app.js</span>', false);
    }

    public function test_links_between_the_pages_point_to_the_site(): void
    {
        $response = $this->get('/docs/getting_started');

        $response->assertSee('href="/docs/pagelets"', false);
        $response->assertDontSee('href="pagelets.md"', false);
    }

    public function test_tables_are_rendered(): void
    {
        $this->get('/docs/dialogs')->assertSee('<table>', false);
    }

    public function test_the_neighbours_of_a_page_are_linked(): void
    {
        $this->get('/docs/how_it_works')
            ->assertSee('class="prev"', false)
            ->assertSee('/docs/getting_started"', false)
            ->assertSee('class="next"', false)
            ->assertSee('/docs/domops"', false);
    }

    public function test_a_page_transition_answers_with_the_page_only(): void
    {
        $_GET = $_REQUEST = ['quickling' => ['version' => '']];

        $response = $this->call('GET', '/docs/pagelets', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $data = json_decode(substr($response->getContent(), strlen('for (;;);')), true);

        $this->assertSame('Pagelets API · Docs · BigPipe', $data['payload']['title']);
        $this->assertStringContainsString('doc-prose', $data['domops'][0][3]['__html']);
        $this->assertStringNotContainsString('<html', $data['domops'][0][3]['__html']);
    }
}
