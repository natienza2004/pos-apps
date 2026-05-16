<x-layouts.app title="Monthly Reports | StockFlow">
    @php
        $kitchen = $departmentCosts->get('Kitchen', 0);
        $bakery = $departmentCosts->get('Bakery', 0);
        $acquisitions = $products->sum(fn ($p) => $p->movements()->where('type', 'In')->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('quantity') * (float) $p->price);
    @endphp
    <div class="page-head">
        <div><h1>Monthly Performance</h1><div class="sub">Consolidated monthly inventory flow and cost analysis.</div></div>
        <form method="GET" style="display:flex;gap:10px"><input type="month" name="month" value="{{ $month->format('Y-m') }}" style="width:170px;margin:0"><button class="btn ghost"><i data-lucide="printer"></i>Print Report</button><button class="btn primary"><i data-lucide="download"></i>Export CSV</button></form>
    </div>
    <section class="grid cards-4">
        <div class="card metric" style="border-left-color:var(--blue)"><small>Avg Monthly Cost</small><strong>₱{{ number_format($totalCost, 2) }}</strong><span>Total consumption value</span></div>
        <div class="card metric" style="border-left-color:var(--green)"><small>Total Acquisitions</small><strong>₱{{ number_format($acquisitions, 2) }}</strong><span>Value of new stock added</span></div>
        <div class="card metric" style="border-left-color:var(--orange)"><small>Kitchen Usage</small><strong>₱{{ number_format($kitchen, 2) }}</strong><span>Cost incurred by Kitchen</span></div>
        <div class="card metric" style="border-left-color:#f43f5e"><small>Bakery Usage</small><strong>₱{{ number_format($bakery, 2) }}</strong><span>Cost incurred by Bakery</span></div>
    </section>
    <div class="card table-card" style="margin-top:28px">
        <table>
            <thead><tr><th>Product / SKU</th><th>Opening</th><th>Stock In</th><th>Kitchen</th><th>Bakery</th><th>Total Usage</th><th>Closing</th><th>Total Cost</th></tr></thead>
            <tbody>
                @foreach ($products as $product)
                    @php
                        $ins = $product->movements()->where('type','In')->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('quantity');
                        $k = $product->movements()->where('type','Out')->where('department','Kitchen')->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('quantity');
                        $b = $product->movements()->where('type','Out')->where('department','Bakery')->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('quantity');
                        $cost = $product->include_in_costing ? ($k + $b) * (float) $product->price : 0;
                    @endphp
                    <tr><td><strong>{{ $product->name }}</strong><br><span class="tiny muted">{{ $product->sku }}</span></td><td>{{ number_format((float) $product->starting_stock, 2) }}</td><td style="color:#059669;font-weight:900">+{{ number_format((float) $ins, 2) }}</td><td>{{ number_format((float) $k, 2) }}</td><td>{{ number_format((float) $b, 2) }}</td><td style="color:#ea6a00;font-weight:900">-{{ number_format((float) $k + (float) $b, 2) }}</td><td><span class="tag"><strong>{{ number_format((float) $product->current_stock, 2) }}</strong></span></td><td class="money">₱{{ number_format($cost, 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td><strong>TOTALS</strong></td><td>{{ number_format($products->sum(fn($p)=>(float)$p->starting_stock), 2) }}</td><td></td><td></td><td></td><td></td><td>{{ number_format($products->sum(fn($p)=>(float)$p->current_stock), 2) }}</td><td class="money">₱{{ number_format($totalCost, 2) }}</td></tr></tfoot>
        </table>
    </div>
    <div class="method"><strong><i data-lucide="activity"></i> Report Methodology</strong><br>This report aggregates data from daily reconciliation records. If days are missed, opening and closing balances are derived from the first and last recorded entries within the month. Costs are calculated based on the unit price at the time of each transaction.</div>
</x-layouts.app>
