<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'user_id', 'product_id', 'quantity'])]
class CartLine extends Model
{
    use BelongsToTenant;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function total(): float
    {
        return $this->quantity * (float) $this->product->price;
    }
}
