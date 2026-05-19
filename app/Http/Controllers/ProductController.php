<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');

        $products = Product::query()
            ->where('user_id', auth()->id())
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->when($status !== 'all', fn ($items) => $items->filter(fn (Product $product): bool => $product->status === $status))
            ->values();

        return view('products.index', [
            'products' => $this->paginateCollection($products, 10, $request),
            'selectedStatus' => $status,
            'nextSku' => $this->nextSku(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();
        $data['current_stock'] = $data['starting_stock'];
        $data['include_in_costing'] = $request->boolean('include_in_costing');

        Product::query()->create($data);

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        $data = $this->validated($request, $product);
        $data['include_in_costing'] = $request->boolean('include_in_costing');

        if (array_key_exists('current_stock', $data)) {
            $movementDelta = $product->movements()
                ->where('created_at', '<=', now()->endOfDay())
                ->get()
                ->reduce(
                    fn (float $carry, $movement): float => $carry + ($movement->type === 'In' ? (float) $movement->quantity : -1 * (float) $movement->quantity),
                    0.0
                );

            $data['starting_stock'] = max(0, (float) $data['current_stock'] - $movementDelta);
        }

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('user_id', auth()->id()))
                    ->ignore($product?->id),
            ],
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
        $next = Product::query()->where('user_id', auth()->id())->count() + 1;

        return 'ITM-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function authorizeProduct(Product $product): void
    {
        abort_unless($product->user_id === auth()->id(), 404);
    }

    private function paginateCollection($items, int $perPage, Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
