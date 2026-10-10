<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Models\CartLine;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        return $this->page('cart.show', ['lines' => $this->lines($request)], 'Cart');
    }

    public function add(Request $request, Product $product)
    {
        $line = $request->user()->cartLines()->firstOrCreate(
            ['product_id' => $product->id],
            ['quantity' => 0]
        );
        $line->increment('quantity');

        return (new AsyncResponse())
            ->setContent('#cart-badge', (string) $request->user()->cartLines()->sum('quantity'))
            ->call('Toastr', 'success', [$product->name.' was added to the cart.'])
            ->send();
    }

    public function remove(Request $request, CartLine $line)
    {
        abort_unless($line->user_id === $request->user()->id, 403);
        $line->delete();

        $lines = $this->lines($request);

        return (new AsyncResponse())
            ->remove('#line-'.$line->id)
            ->setContent('#cart-total', number_format($lines->sum->total(), 2))
            ->setContent('#cart-badge', (string) $lines->sum('quantity'))
            ->setContent('#cart-empty', $lines->isEmpty() ? 'Your cart is empty.' : '')
            ->send();
    }

    private function lines(Request $request)
    {
        return $request->user()->cartLines()->with('product')->orderBy('id')->get();
    }
}
