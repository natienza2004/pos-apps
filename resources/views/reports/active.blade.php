<x-layouts.app title="Active Inventory | StockFlow">
    @php
        $low = $products->filter(fn ($p) => $p->status === 'Low')->count();
        $out = $products->filter(fn ($p) => $p->status === 'Out')->count();
    @endphp
    <div class="page-head">
        <div><h1>Active Inventory</h1><div class="sub">Live tracking of your current warehouse stock levels.</div></div>
        <div style="display:flex;gap:10px"><a class="btn green" href="{{ route('stock.in') }}"><i data-lucide="circle-plus"></i>Stock In</a><a class="btn orange" href="{{ route('stock.out') }}"><i data-lucide="circle-minus"></i>Stock Out</a></div>
    </div>

    <section class="grid cards-3" style="margin-bottom:28px">
        <div class="card metric" style="border-left-color:var(--blue)"><small>Total Items</small><strong>{{ $products->count() }}</strong></div>
        <div class="card metric" style="border-left-color:var(--orange)"><small>Low Stock</small><strong style="color:#d97706">{{ $low }}</strong></div>
        <div class="card metric" style="border-left-color:var(--red)"><small>Out of Stock</small><strong style="color:#dc2626">{{ $out }}</strong></div>
    </section>

    <div class="card table-card">
        <div class="panel-body" style="display:flex;justify-content:space-between;gap:14px">
            <div class="search-wrap"><i data-lucide="search"></i><input class="search" style="border:1px solid #bfc8d7;border-radius:6px;width:380px;background:#fff" placeholder="Search by name or SKU..."></div>
            <select style="width:115px;margin:0"><option>All</option><option>Low</option><option>Out</option></select>
        </div>
        <table>
            <thead>
                <tr><th>Product Details</th><th>Category</th><th>Unit</th><th>Current Stock</th><th>Unit Price</th><th>Total Value</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td><strong>{{ $product->name }}</strong><br><span class="tiny muted">{{ $product->sku }}</span></td>
                        <td><span class="tag">{{ $product->category }}</span></td>
                        <td>{{ $product->unit }}</td>
                        <td><strong style="font-size:16px">{{ number_format((float) $product->current_stock, 2) }}</strong></td>
                        <td class="muted">₱{{ number_format((float) $product->price, 2) }}</td>
                        <td class="money">₱{{ number_format($product->inventory_value, 2) }}</td>
                        <td><span class="badge {{ $product->status === 'In Stock' ? 'green' : ($product->status === 'Low' ? 'orange' : 'red') }}">{{ $product->status }}</span></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="5" style="text-align:right;font-weight:900">TOTAL INVENTORY VALUE</td><td colspan="2" class="money" style="font-size:17px">₱{{ number_format($products->sum(fn ($p) => $p->inventory_value), 2) }}</td></tr></tfoot>
        </table>
    </div>
</x-layouts.app>
