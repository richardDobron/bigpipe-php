<?php

namespace Tests\Feature;

use App\Models\CartLine;
use App\Models\Post;
use App\Models\Product;
use App\Tenancy\Playground;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_visitor_gets_a_playground_of_their_own(): void
    {
        $this->get('/app/posts')->assertOk();

        $first = \App\Models\User::firstOrFail();
        $this->assertNotNull($first->tenant_id);
        $this->assertSame(35, Post::withoutGlobalScopes()->where('tenant_id', $first->tenant_id)->count());

        $this->flushSession();
        auth()->logout();
        $this->get('/app/posts')->assertOk();

        $this->assertSame(2, \App\Models\User::distinct('tenant_id')->count('tenant_id'));
    }

    public function test_a_visitor_sees_only_their_own_records(): void
    {
        $one = Playground::create();
        $two = Playground::create();

        $this->actingAs($one);
        $this->assertSame(35, Post::count());
        $this->assertSame(6, Product::count());

        Post::first()->delete();
        $this->assertSame(34, Post::count());

        $this->actingAs($two);
        $this->assertSame(35, Post::count());
    }

    public function test_new_records_belong_to_the_tenant_of_the_user(): void
    {
        $user = Playground::create();
        $this->actingAs($user);

        $line = CartLine::create(['user_id' => $user->id, 'product_id' => Product::first()->id]);

        $this->assertSame($user->tenant_id, $line->tenant_id);
    }

    public function test_old_playgrounds_are_pruned(): void
    {
        $old = Playground::create();
        $old->forceFill(['created_at' => now()->subHours(2)])->save();
        $fresh = Playground::create();

        $this->assertSame(1, Playground::prune());

        $this->assertNull(\App\Models\User::find($old->id));
        $this->assertSame(0, Product::withoutGlobalScopes()->where('tenant_id', $old->tenant_id)->count());
        $this->assertSame(0, Post::withoutGlobalScopes()->where('tenant_id', $old->tenant_id)->count());
        $this->assertSame(6, Product::withoutGlobalScopes()->where('tenant_id', $fresh->tenant_id)->count());
    }
}
