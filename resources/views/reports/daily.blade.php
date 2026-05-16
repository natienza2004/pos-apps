<x-layouts.app title="Daily Reports | StockFlow">
    @php
        $kitchenTotal = $records->sum(fn ($r) => (float) $r->kitchen_cost);
        $bakeryTotal = $records->sum(fn ($r) => (float) $r->bakery_cost);
        $low = $records->filter(fn ($r) => $r->product->status === 'Low')->count();
        $out = $records->filter(fn ($r) => $r->product->status === 'Out')->count();
        $costing = $records->filter(fn ($r) => $r->product->include_in_costing)->count();
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
            <button class="btn ghost" type="button"><i data-lucide="printer"></i>Print</button>
            <button class="btn ghost" type="button"><i data-lucide="download"></i>Export</button>
        </div>
    </div>

    <div class="card table-card">
        <div class="panel-body" style="display:flex;justify-content:space-between;gap:14px">
            <div class="search-wrap"><i data-lucide="search"></i><input class="search" style="border:1px solid #bfc8d7;border-radius:6px;width:380px;background:#fff" placeholder="Quick search products..."></div>
            <label style="display:flex;gap:8px;align-items:center;color:#64748b"><input type="checkbox" style="width:auto;min-height:auto;margin:0"> Includes In-Costing Items Only</label>
        </div>
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
                        <td><strong>{{ $record->product->name }}</strong>@unless($record->product->include_in_costing) <span class="tag">EXCL.</span>@endunless</td>
                        <td>{{ $record->product->unit }}</td>
                        <td class="muted">&#8369;{{ number_format((float) $record->product->price, 2) }}</td>
                        <td class="money">{{ number_format((float) $record->starting_stock, 2) }}</td>
                        <td style="color:#059669;font-weight:900">+{{ number_format((float) $record->stock_in, 2) }}</td>
                        <td style="color:#ea6a00">-{{ number_format((float) $record->kitchen_out, 2) }}</td>
                        <td style="color:#ea6a00">-{{ number_format((float) $record->bakery_out, 2) }}</td>
                        <td><strong>-{{ number_format($totalOut, 2) }}</strong></td>
                        <td class="money" style="background:#eef2ff">{{ number_format((float) $record->ending_stock, 2) }}</td>
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
    </div>

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
