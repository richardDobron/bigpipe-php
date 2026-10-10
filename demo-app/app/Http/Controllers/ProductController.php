<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return $this->page('products.index', ['products' => Product::orderBy('name')->get()], 'Shop');
    }
}
