<x-layouts.app title="Daily Reports | StockFlow">
    <style>
        @media print {
            .sidebar, .topbar, .page-head > div:last-child, .search-wrap, .form-actions, .toast { display: none !important; }
            .app { display: block; }
            .content { padding: 0; }
            .container { width: 100%; max-width: none; }
            .card { box-shadow: none; border-color: #cbd5e1; }
            .table-card { overflow: visible; }
            th, td { padding: 8px; font-size: 11px; }
            body { background: #fff; }
        }
    </style>

    @php
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
        $formatMoneyInput = fn ($value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        $kitchenTotal = $allRecords->sum(fn ($r) => (float) $r->kitchen_cost);
        $bakeryTotal = $allRecords->sum(fn ($r) => (float) $r->bakery_cost);
        $low = $allRecords->filter(fn ($r) => $r->product->status === 'Low')->count();
        $out = $allRecords->filter(fn ($r) => $r->product->status === 'Out')->count();
        $costing = $allRecords->filter(fn ($r) => $r->product->include_in_costing)->count();
        $isEditing = $editing ?? request()->boolean('edit');
        $isFutureDate = $date->isFuture();
        $categories = ['Kitchen', 'Bakery', 'Raw Material', 'Packaging'];
        $units = [
            'grams' => 'Grams',
            'liters' => 'Liters',
            'kilograms' => 'Kilograms',
            'piece' => 'Piece',
            'pack' => 'Pack',
            'bags' => 'Bags',
        ];
    @endphp

    <div class="page-head">
        <div>
            <h1>Daily Reconciliation</h1>
            <div class="sub">Daily overview of stock levels, movements and costing per product.</div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
            <form method="GET" id="daily-date-form" class="date-nav">
                <a href="{{ route('reports.daily', ['date' => $previousDate->toDateString()]) }}" aria-label="Previous day"><i data-lucide="chevron-left"></i></a>
                <strong>{{ $date->format('d/m/Y') }}</strong>
                <button type="button" aria-label="Pick date" onclick="document.getElementById('daily-date-input').showPicker ? document.getElementById('daily-date-input').showPicker() : document.getElementById('daily-date-input').click()">
                    <i data-lucide="calendar"></i>
                </button>
                <a href="{{ route('reports.daily', ['date' => $nextDate->toDateString()]) }}" aria-label="Next day"><i data-lucide="chevron-right"></i></a>
                <input id="daily-date-input" type="date" name="date" value="{{ $date->toDateString() }}" onchange="document.getElementById('daily-date-form').submit()">
            </form>
            <a class="btn green" href="{{ route('stock.in') }}"><i data-lucide="circle-plus"></i>Stock In</a>
            <a class="btn orange" href="{{ route('stock.out') }}"><i data-lucide="circle-minus"></i>Stock Out</a>
            @if ($isEditing)
                <a class="btn ghost" href="{{ route('reports.daily', ['date' => $date->toDateString()]) }}"><i data-lucide="x"></i>Cancel Edit</a>
            @elseif (! $isFutureDate)
                <a class="btn primary" href="{{ route('reports.daily', ['date' => $date->toDateString(), 'edit' => 1]) }}"><i data-lucide="pencil"></i>Edit Sheet</a>
            @endif
            <button class="btn ghost" type="button" onclick="window.print()"><i data-lucide="printer"></i>Print</button>
            <a class="btn ghost" href="{{ route('reports.daily.export', ['date' => $date->toDateString()]) }}"><i data-lucide="download"></i>Export</a>
        </div>
    </div>

    <div class="card table-card">
        <div class="panel-body" style="display:flex;justify-content:space-between;gap:14px">
            <div class="search-wrap"><i data-lucide="search"></i><input class="search" style="border:1px solid #bfc8d7;border-radius:6px;width:380px;background:#fff" placeholder="Quick search products..."></div>
            @if ($isEditing)
                <span class="muted" style="align-self:center">Editing {{ $date->format('M d, Y') }}</span>
            @endif
        </div>
        @if ($isEditing)
            <form method="POST" action="{{ route('reports.daily.update') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        @endif
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Start Stock</th>
                    <th>Stock In</th>
                    <th>Kitchen Out</th>
                    <th>Bakery Out</th>
                    <th>Total Out</th>
                    <th>Ending Stock</th>
                    <th>Kitchen Cost</th>
                    <th>Bakery Cost</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    @php
                        $totalOut = (float) $record->kitchen_out + (float) $record->bakery_out;
                    @endphp
                    <tr>
                        <td><a href="#edit-product-{{ $record->product->id }}" style="font-weight:900;color:var(--blue)">{{ $record->product->name }}</a>@unless($record->product->include_in_costing) <span class="tag">EXCL.</span>@endunless</td>
                        <td>{{ $record->product->unit }}</td>
                        <td class="muted">&#8369;{{ number_format((float) $record->product->price, 2) }}</td>
                        <td class="money">{{ $formatQuantity($record->starting_stock) }}</td>
                        <td style="color:#059669;font-weight:900">
                            @if ($isEditing)
                                <input class="sheet-input in" type="number" step="0.001" min="0" name="rows[{{ $record->product_id }}][stock_in]" value="{{ (float) $record->stock_in }}">
                            @else
                                +{{ $formatQuantity($record->stock_in) }}
                            @endif
                        </td>
                        <td style="color:#ea6a00">
                            @if ($isEditing)
                                <input class="sheet-input out" type="number" step="0.001" min="0" name="rows[{{ $record->product_id }}][kitchen_out]" value="{{ (float) $record->kitchen_out }}">
                            @else
                                -{{ $formatQuantity($record->kitchen_out) }}
                            @endif
                        </td>
                        <td style="color:#ea6a00">
                            @if ($isEditing)
                                <input class="sheet-input out" type="number" step="0.001" min="0" name="rows[{{ $record->product_id }}][bakery_out]" value="{{ (float) $record->bakery_out }}">
                            @else
                                -{{ $formatQuantity($record->bakery_out) }}
                            @endif
                        </td>
                        <td><strong>-{{ $formatQuantity($totalOut) }}</strong></td>
                        <td class="money" style="background:#eef2ff">{{ $formatQuantity($record->ending_stock) }}</td>
                        <td>&#8369;{{ number_format((float) $record->kitchen_cost, 2) }}</td>
                        <td>&#8369;{{ number_format((float) $record->bakery_cost, 2) }}</td>
                        <td><strong>&#8369;{{ number_format((float) $record->total_cost, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="9"><strong>DAILY TOTALS</strong></td>
                    <td><strong>&#8369;{{ number_format($kitchenTotal, 2) }}</strong></td>
                    <td><strong>&#8369;{{ number_format($bakeryTotal, 2) }}</strong></td>
                    <td style="background:#4f36f5;color:#fff"><strong>&#8369;{{ number_format($kitchenTotal + $bakeryTotal, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @if ($isEditing)
                <div class="panel-body" style="display:flex;justify-content:space-between;align-items:center;gap:14px;border-top:1px solid var(--line)">
                    <span class="muted">Save only corrections for stock in, kitchen out, and bakery out. Future stock-out dates are blocked.</span>
                    <button class="btn primary" type="submit"><i data-lucide="save"></i>Save Sheet Changes</button>
                </div>
            </form>
        @endif
    </div>

    <div style="margin-top:16px">{{ $records->links() }}</div>

    @foreach ($records as $record)
        @php($product = $record->product)
        <div id="edit-product-{{ $product->id }}" class="modal-backdrop">
            <div class="modal">
                <div class="modal-head">
                    <span>Edit Product</span>
                    <a href="#"><i data-lucide="x"></i></a>
                </div>
                <form method="POST" action="{{ route('products.update', $product) }}" class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <label>Product Name<input name="name" value="{{ $product->name }}" required></label>
                        <label>SKU<input name="sku" value="{{ $product->sku }}" required></label>
                        <label>Category
                            <select name="category" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($product->category === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Unit
                            <select name="unit" required>
                                @foreach ($units as $value => $label)
                                    <option value="{{ $value }}" @selected($product->unit === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Price per Unit<input type="number" step="0.01" min="0" name="price" value="{{ $formatMoneyInput($product->price) }}" required></label>
                        <label>Starting Stock<input type="number" step="0.001" min="0" name="starting_stock" value="{{ $formatQuantity($product->starting_stock) }}" required></label>
                        <label>Current Stock<input type="number" step="0.001" min="0" name="current_stock" value="{{ $formatQuantity($product->current_stock) }}" required></label>
                        <label>Low Stock Threshold<input type="number" min="0" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}" required></label>
                        <label style="border:1px solid #d9dee7;border-radius:6px;padding:12px;margin-top:20px">
                            <input type="checkbox" name="include_in_costing" value="1" @checked($product->include_in_costing) style="width:auto;min-height:auto;margin:0 8px 0 0">Include in Costing
                            <br><span class="tiny muted">Will be included in financial calculations</span>
                        </label>
                    </div>
                    <div class="form-actions">
                        <a class="btn ghost" href="#">Cancel</a>
                        <button class="btn primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <section class="grid two" style="margin-top:28px">
        <div class="card">
            <div class="panel-head"><span><i data-lucide="dollar-sign" style="width:15px;color:var(--blue)"></i> Daily Costing Summary</span></div>
            <div class="panel-body">
                <div style="padding:20px;border-radius:9px;background:#fff8e6;border:1px solid #fde68a;display:flex;justify-content:space-between">
                    <span><strong style="background:#f59e0b;color:#fff;border-radius:999px;padding:10px 13px;margin-right:12px">K</strong>Kitchen Total Cost</span>
                    <strong style="font-size:22px;color:#92400e">&#8369;{{ number_format($kitchenTotal, 2) }}</strong>
                </div>
                <div style="margin-top:16px;padding:20px;border-radius:9px;background:#eef2ff;border:1px solid #dbe3ff;display:flex;justify-content:space-between">
                    <span><strong style="background:#6366f1;color:#fff;border-radius:999px;padding:10px 13px;margin-right:12px">B</strong>Bakery Total Cost</span>
                    <strong style="font-size:22px;color:#312e81">&#8369;{{ number_format($bakeryTotal, 2) }}</strong>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="panel-head"><span><i data-lucide="package" style="width:15px;color:var(--blue)"></i> Stock Health</span></div>
            <div class="panel-body grid cards-3">
                <div class="card panel-body" style="text-align:center"><small class="muted">LOW STOCK ITEMS</small><strong style="display:block;font-size:28px;color:#d97706">{{ $low }}</strong></div>
                <div class="card panel-body" style="text-align:center"><small class="muted">OUT OF STOCK</small><strong style="display:block;font-size:28px;color:#dc2626">{{ $out }}</strong></div>
                <div class="card panel-body" style="text-align:center"><small class="muted">INCLUDE IN COSTING</small><strong style="display:block;font-size:28px">{{ $costing }}</strong></div>
            </div>
        </div>
    </section>
</x-layouts.app>
