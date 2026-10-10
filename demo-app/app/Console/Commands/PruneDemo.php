<?php

namespace App\Console\Commands;

use App\Tenancy\Playground;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:prune')]
#[Description('Remove the playgrounds of the visitors that are older than an hour')]
class PruneDemo extends Command
{
    public function handle(): void
    {
        $this->info(Playground::prune().' playgrounds removed.');
    }
}
