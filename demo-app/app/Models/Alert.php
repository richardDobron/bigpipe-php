<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'title', 'read_at'])]
class Alert extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
