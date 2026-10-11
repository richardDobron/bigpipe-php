<?php

namespace Tests\Feature;

use App\Docs\Docs;
use Tests\TestCase;

class DocsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A page transition test sets the request of BigPipe, see Quickling::isRequested().
        $_GET = $_REQUEST = [];
    }

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

    public function test_every_page_of_an_older_version_renders_with_its_links(): void
    {
        $docs = new Docs('2.0');

        foreach ($docs->slugs() as $slug) {
            if ($docs->exists($slug)) {
                $this->get("/docs/2.0/{$slug}")->assertOk()->assertSee('docs-outdated', false);
            }
        }

        $this->get('/docs/2.0/getting_started')
            ->assertSee('href="/docs/2.0/pagelets"', false)
            ->assertSee('blob/2.0.0/docs/getting_started.md', false)
            ->assertSee('href="/docs/getting_started"', false);
    }

    public function test_an_older_version_shows_only_the_pages_it_has(): void
    {
        $this->get('/docs/2.0/getting_started')->assertDontSee('/docs/2.0/symfony_integration', false);
        $this->get('/docs/2.0/symfony_integration')->assertNotFound();
    }

    public function test_the_latest_version_is_not_outdated_and_links_to_the_older_one(): void
    {
        $this->get('/docs/pagelets')
            ->assertDontSee('docs-outdated', false)
            ->assertSee('href="/docs/2.0/pagelets"', false);
    }

    public function test_the_versions_lead_to_their_pages(): void
    {
        $this->get('/docs/2.0')->assertRedirect('/docs/2.0/getting_started');
        $this->get('/docs/2.1/pagelets')->assertRedirect('/docs/pagelets');
        $this->get('/docs/9.9/pagelets')->assertNotFound();
    }
}
