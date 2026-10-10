<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'demo@example.com')->firstOrFail();
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

        return $this->actingAs($this->user)->call($method, $uri, $data, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
    }

    public function test_the_pages_render(): void
    {
        foreach (['/app/posts', '/app/shop', '/app/cart', '/app/notifications', '/app/profile', '/tutorial/configuration', '/tutorial/dom-references', '/tutorial/csrf'] as $uri) {
            $this->actingAs($this->user)->get($uri)->assertOk();
        }
    }

    public function test_a_page_transition_answers_with_the_content_only(): void
    {
        $data = $this->bigpipe($this->ajax('GET', '/app/shop?quickling[version]='));

        $this->assertSame('Shop · BigPipe', $data['payload']['title']);
        $this->assertSame('setContent', $data['domops'][0][0]);
        $this->assertStringContainsString('Add to cart', $data['domops'][0][3]['__html']);
        $this->assertStringNotContainsString('<html', $data['domops'][0][3]['__html']);
    }

    public function test_a_transition_keeps_the_pagelets_and_modules_of_the_content(): void
    {
        $data = $this->bigpipe($this->ajax('GET', '/app/posts/'.Post::first()->id.'/edit?quickling[version]='));

        $modules = array_column($data['jsmods']['require'], 0);
        $this->assertContains('bigpipe-util/dist/core/FormMonitor', $modules);
    }

    public function test_the_next_posts_are_appended_and_the_pager_replaced(): void
    {
        $data = $this->bigpipe($this->ajax('GET', '/app/posts?page=2'));

        $this->assertSame('appendContent', $data['domops'][0][0]);
        $this->assertSame('#posts', $data['domops'][0][1]);
        $this->assertSame('replace', $data['domops'][1][0]);
    }

    public function test_a_comment_is_prepended_to_the_list(): void
    {
        $post = Post::first();

        $data = $this->bigpipe($this->ajax('POST', "/app/posts/{$post->id}/comments", ['body' => 'Nice!']));

        $this->assertSame('prependContent', $data['domops'][0][0]);
        $this->assertSame('#comments', $data['domops'][0][1]);
        $this->assertStringContainsString('Nice!', $data['domops'][0][3]['__html']);
        $this->assertSame('Form/Reset', $data['jsmods']['require'][0][0]);
    }

    public function test_an_empty_comment_is_rejected_with_a_message_at_the_field(): void
    {
        $data = $this->bigpipe($this->ajax('POST', '/app/posts/'.Post::first()->id.'/comments', ['body' => '']));

        $this->assertSame(422, $data['error']);
        $this->assertContains(
            ['setContent', '#error-body', false, ['__html' => 'The body field is required.']],
            $data['domops']
        );
    }

    public function test_the_cart_updates_the_badge_and_the_lines(): void
    {
        $product = Product::first();

        $this->ajax('POST', "/app/cart/add/{$product->id}")->assertOk();
        $line = $this->user->cartLines()->firstOrFail();

        $data = $this->bigpipe($this->ajax('POST', "/app/cart/remove/{$line->id}"));

        $this->assertSame(['remove', '#line-'.$line->id], array_slice($data['domops'][0], 0, 2));
        $this->assertContains(['setContent', '#cart-badge', false, ['__html' => '0']], $data['domops']);
        $this->assertSame(0, $this->user->cartLines()->count());
    }

    public function test_the_delete_dialog_closes_itself_on_success(): void
    {
        $data = $this->bigpipe($this->ajax('POST', '/app/posts/'.Post::first()->id.'/delete-dialog'));

        [$module, $method, $args] = $data['jsmods']['require'][0];

        $this->assertSame('bigpipe-util/dist/core/Dialog', $module);
        $this->assertSame('form', $args[0]['hideOnSuccess']);
        $this->assertSame('static', $args[0]['backdrop']);
    }

    public function test_deleting_a_post_goes_back_to_all_posts(): void
    {
        $post = Post::first();

        $data = $this->bigpipe($this->ajax('DELETE', '/app/posts/'.$post->id));

        $this->assertContains(
            ['bigpipe-util/dist/PageTransitions', 'go', [route('posts.index')]],
            array_map(fn (array $call) => array_slice($call, 0, 3), $data['jsmods']['require'])
        );
        $this->assertModelMissing($post);
    }

    public function test_the_notifications_are_polled(): void
    {
        $data = $this->bigpipe($this->ajax('GET', '/app/notifications/poll'));

        $this->assertContains(['setContent', '#unread-count', false, ['__html' => '3']], $data['domops']);
    }

    public function test_the_dashboard_streams_its_pagelets_in_a_transition(): void
    {
        $response = $this->ajax('GET', '/app/dashboard?quickling[version]=&__stream=1');

        $parts = array_filter(explode('/*<!-- fetch-stream -->*/', $response->streamedContent()));
        $ids = [];
        foreach ($parts as $part) {
            foreach (json_decode($part, true)['content']['pagelets'] ?? [] as $pagelet) {
                $ids[] = $pagelet['id'];
            }
        }

        $this->assertEqualsCanonicalizing(['revenue', 'activity', '__bigpipe_last'], $ids);
    }

    public function test_an_expired_csrf_token_is_answered_with_a_new_one(): void
    {
        // The tests skip the CSRF check, so a route throws what the check would.
        Route::middleware('web')->post('/_expired', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $data = $this->bigpipe($this->ajax('POST', '/_expired'));

        $this->assertSame(1, $data['csrf_refresh']);
        $this->assertSame('CSRFToken', $data['jsmods']['define'][0][0]);
    }
}
