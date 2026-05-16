<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings', [
            'companyName' => Setting::getValue('company_name', 'StockFlow Bakery & Kitchen'),
            'companyAddress' => Setting::getValue('company_address', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::setValue('company_name', $data['company_name']);
        Setting::setValue('company_address', $data['company_address'] ?? '');

        return back()->with('success', 'Settings saved.');
    }

    public function reset(): RedirectResponse
    {
        DB::transaction(function (): void {
            InventoryRecord::query()->delete();
            StockMovement::query()->delete();
            Product::query()->delete();
        });

        return back()->with('success', 'Inventory data reset.');
    }
}
