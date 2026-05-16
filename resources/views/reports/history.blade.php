<x-layouts.app title="Inventory History | StockFlow">
    <div class="page-head">
        <div>
            <h1>Stock Movement History</h1>
            <div class="sub">Audit trail of every stock in and stock out operation.</div>
        </div>
    </div>

    <div class="card table-card">
        <form method="GET" class="panel-body" style="display:grid;grid-template-columns:1.4fr repeat(5, minmax(140px, 1fr));gap:12px;align-items:end">
            <label>Search
                <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Product, SKU, or reference...">
            </label>
            <label>From
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>To
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <label>Product
                <select name="product_id">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') === $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Movement
                <select name="type">
                    <option value="">All Types</option>
                    <option value="In" @selected(($filters['type'] ?? '') === 'In')>Stock In</option>
                    <option value="Out" @selected(($filters['type'] ?? '') === 'Out')>Stock Out</option>
                </select>
            </label>
            <label>Department
                <select name="department">
                    <option value="">All Sources</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected(($filters['department'] ?? '') === $department)>{{ $department }}</option>
                    @endforeach
                </select>
            </label>
            <div style="grid-column:1 / -1;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn small ghost" href="{{ route('reports.history', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"><i data-lucide="calendar-days"></i>Today</a>
                    <a class="btn small ghost" href="{{ route('reports.history', ['date_from' => now()->subDays(6)->toDateString(), 'date_to' => now()->toDateString()]) }}"><i data-lucide="calendar-range"></i>Last 7 Days</a>
                    <a class="btn small ghost" href="{{ route('reports.history', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()]) }}"><i data-lucide="calendar"></i>This Month</a>
                </div>
                <div style="display:flex;gap:8px">
                    <a class="btn ghost" href="{{ route('reports.history') }}">Reset</a>
                    <button class="btn primary"><i data-lucide="filter"></i>Apply Filters</button>
                </div>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    <th>Movement</th>
                    <th>Quantity</th>
                    <th>Dept / Source</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td><strong>{{ $movement->created_at->format('M d, Y') }}</strong><br><span class="tiny muted">{{ $movement->created_at->format('h:i A') }}</span></td>
                        <td><strong>{{ $movement->product->name }}</strong><br><span class="tiny muted">{{ $movement->product->sku }}</span></td>
                        <td><span class="badge {{ $movement->type === 'In' ? 'green' : 'orange' }}">{{ $movement->type === 'In' ? 'Stock In' : 'Stock Out' }}</span></td>
                        <td style="font-weight:900;color:{{ $movement->type === 'In' ? '#00a872' : '#ea6a00' }}">{{ $movement->type === 'In' ? '+' : '-' }}{{ number_format((float) $movement->quantity, 0) }} {{ $movement->product->unit }}</td>
                        <td><span class="tag">{{ $movement->department }}</span></td>
                        <td class="muted">{{ $movement->reason ?: 'N/A' }}</td>
                        <td><i data-lucide="user" style="width:13px"></i> {{ $movement->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No movements match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px">{{ $movements->links() }}</div>
</x-layouts.app>
