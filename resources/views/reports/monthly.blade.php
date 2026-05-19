<x-layouts.app title="Monthly Reports | StockFlow">
    @php
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
        $kitchen = $departmentCosts->get('Kitchen', 0);
        $bakery = $departmentCosts->get('Bakery', 0);
        $acquisitions = $allProducts->sum(fn ($p) => $p->movements()->where('type', 'In')->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('quantity') * (float) $p->price);
    @endphp
    <div class="page-head">
        <div><h1>Monthly Performance</h1><div class="sub">Consolidated monthly inventory flow and cost analysis.</div></div>
        <form method="GET" style="display:flex;gap:10px;align-items:center">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" data-auto-submit style="width:170px;margin:0">
            <button class="btn primary"><i data-lucide="calendar"></i>Apply Month</button>
        </form>
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
                    <tr><td><strong>{{ $product->name }}</strong><br><span class="tiny muted">{{ $product->sku }}</span></td><td>{{ $formatQuantity($product->starting_stock) }}</td><td style="color:#059669;font-weight:900">+{{ $formatQuantity($ins) }}</td><td>{{ $formatQuantity($k) }}</td><td>{{ $formatQuantity($b) }}</td><td style="color:#ea6a00;font-weight:900">-{{ $formatQuantity((float) $k + (float) $b) }}</td><td><span class="tag"><strong>{{ $formatQuantity($product->current_stock) }}</strong></span></td><td class="money">₱{{ number_format($cost, 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td><strong>TOTALS</strong></td><td>{{ $formatQuantity($allProducts->sum(fn($p)=>(float)$p->starting_stock)) }}</td><td></td><td></td><td></td><td></td><td>{{ $formatQuantity($allProducts->sum(fn($p)=>(float)$p->current_stock)) }}</td><td class="money">₱{{ number_format($totalCost, 2) }}</td></tr></tfoot>
        </table>
    </div>
    <div style="margin-top:16px">{{ $products->links() }}</div>
    <div class="method"><strong><i data-lucide="activity"></i> Report Methodology</strong><br>This report aggregates data from daily reconciliation records. If days are missed, opening and closing balances are derived from the first and last recorded entries within the month. Costs are calculated based on the unit price at the time of each transaction.</div>
</x-layouts.app>
