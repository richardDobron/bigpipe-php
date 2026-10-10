<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'user_id', 'title', 'read_at'])]
class Alert extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
