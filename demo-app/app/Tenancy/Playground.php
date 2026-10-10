<?php

namespace App\Tenancy;

use App\Models\Alert;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A playground is a user with a tenant of their own and the sample data of the demo.
 */
class Playground
{
    public const LIFETIME_MINUTES = 60;

    public static function create(?string $email = null): User
    {
        $tenant = (string) Str::uuid();

        $user = User::create([
            'name' => 'Demo User',
            'email' => $email ?? "demo-{$tenant}@example.com",
            'password' => Hash::make(Str::random(40)),
            'tenant_id' => $tenant,
        ]);

        static::seed($user);

        return $user;
    }

    public static function seed(User $user): void
    {
        $tenant = $user->tenant_id;

        foreach (range(1, 35) as $ignored) {
            $post = Post::create([
                'tenant_id' => $tenant,
                'user_id' => $user->id,
                'title' => fake()->sentence(rand(3, 7)),
                'body' => fake()->paragraphs(rand(2, 4), true),
            ]);

            foreach (range(1, rand(0, 3)) as $ignored) {
                Comment::create(['tenant_id' => $tenant, 'post_id' => $post->id, 'user_id' => $user->id, 'body' => fake()->sentence(12)]);
            }
        }

        foreach ([
            'Wireless headphones' => 79.90,
            'Mechanical keyboard' => 119.00,
            'USB-C dock' => 54.50,
            'Laptop stand' => 32.00,
            'Webcam 1080p' => 45.99,
            'Desk lamp' => 24.90,
        ] as $name => $price) {
            Product::create(['tenant_id' => $tenant, 'name' => $name, 'price' => $price]);
        }

        foreach (['Welcome to the demo', 'Your order has shipped', 'A new comment was added'] as $title) {
            Alert::create(['tenant_id' => $tenant, 'user_id' => $user->id, 'title' => $title]);
        }
    }

    /**
     * Removes the playgrounds older than their lifetime, with everything in them.
     */
    public static function prune(): int
    {
        $stale = User::whereNotNull('tenant_id')->where('created_at', '<', now()->subMinutes(static::LIFETIME_MINUTES));

        Product::withoutGlobalScopes()->whereIn('tenant_id', (clone $stale)->select('tenant_id'))->delete();

        return $stale->delete();
    }
}
