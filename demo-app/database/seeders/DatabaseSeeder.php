<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make(Str::random(40))]
        );

        foreach (range(1, 35) as $number) {
            $post = Post::create([
                'user_id' => $user->id,
                'title' => fake()->sentence(rand(3, 7)),
                'body' => fake()->paragraphs(rand(2, 4), true),
            ]);

            foreach (range(1, rand(0, 3)) as $ignored) {
                Comment::create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => fake()->sentence(12)]);
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
            Product::create(['name' => $name, 'price' => $price]);
        }

        foreach (['Welcome to the demo', 'Your order has shipped', 'A new comment was added'] as $title) {
            Alert::create(['user_id' => $user->id, 'title' => $title]);
        }
    }
}
