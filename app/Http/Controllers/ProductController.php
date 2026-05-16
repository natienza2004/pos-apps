<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');

        $products = Product::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->when($status !== 'all', fn ($items) => $items->filter(fn (Product $product): bool => $product->status === $status))
            ->values();

        return view('products.index', [
            'products' => $products,
            'selectedStatus' => $status,
            'nextSku' => $this->nextSku(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['current_stock'] = $data['starting_stock'];
        $data['include_in_costing'] = $request->boolean('include_in_costing');

        Product::query()->create($data);

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $data['include_in_costing'] = $request->boolean('include_in_costing');

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,'.($product?->id ?? 'NULL').',id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0'],
            'starting_stock' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['sometimes', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function nextSku(): string
    {
        $next = Product::query()->count() + 1;

        return 'ITM-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
