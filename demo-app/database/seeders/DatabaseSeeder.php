<?php

namespace Database\Seeders;

use App\Tenancy\Playground;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Playground::create('demo@example.com');
    }
}
