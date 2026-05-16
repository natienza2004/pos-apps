<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(): View
    {
        return view('movements.index', [
            'products' => Product::query()->orderBy('name')->get(),
            'movements' => StockMovement::query()->with(['product', 'user'])->latest()->paginate(15),
        ]);
    }

    public function stockIn(): View
    {
        return view('movements.stock-in', [
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }

    public function stockOut(): View
    {
        return view('movements.stock-out', [
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:In,Out'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'department' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:255'],
            'movement_date' => ['required', 'date'],
        ]);

        try {
            DB::transaction(function () use ($data): void {
                $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
                $quantity = (float) $data['quantity'];
                $current = (float) $product->current_stock;

                if ($data['type'] === 'Out' && $quantity > $current) {
                    throw new \RuntimeException('Stock-out quantity is higher than available stock.');
                }

                $product->update([
                    'current_stock' => $data['type'] === 'In' ? $current + $quantity : $current - $quantity,
                ]);

                $movementAt = Carbon::parse($data['movement_date'])->setTimeFrom(Carbon::now());
                unset($data['movement_date']);

                StockMovement::query()->create($data + [
                    'user_id' => $this->systemUser()->id,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        InventoryRecord::rebuildForDate(Carbon::parse($request->input('movement_date')));

        return back()->with('success', $data['type'] === 'In' ? 'Stock added.' : 'Stock released.');
    }

    private function systemUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'system@stockflow.local'],
            ['name' => 'StockFlow Operator', 'role' => 'Admin', 'password' => 'password']
        );
    }
}
