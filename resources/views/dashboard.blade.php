<x-layouts.app title="StockFlow POS & Inventory">
    @php
        $kitchen = $filteredKitchenCost;
        $bakery = $filteredBakeryCost;
        $total = max($kitchen + $bakery, 1);
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
    @endphp
    <div class="page-head">
        <div>
            <h1>System Overview</h1>
            <div class="sub">Real-time inventory health and costing insights.</div>
        </div>
        <div style="display:flex;gap:10px">
            <a class="btn green" href="{{ route('stock.in') }}"><i data-lucide="plus"></i>Stock In</a>
            <a class="btn orange" href="{{ route('stock.out') }}"><i data-lucide="minus"></i>Stock Out</a>
        </div>
    </div>

    <section class="grid cards-4">
        <div class="card metric"><div class="icon" style="background:#eef2ff;color:var(--blue)"><i data-lucide="dollar-sign"></i></div><small>Inventory Value</small><strong>&#8369;{{ number_format($totalInventoryValue, 2) }}</strong></div>
        <div class="card metric"><div class="icon" style="background:#fff7ed;color:var(--orange)"><i data-lucide="activity"></i></div><small>Kitchen Usage</small><strong>&#8369;{{ number_format($kitchen, 2) }}</strong><span>{{ $chartFrom->format('M d') }} - {{ $chartTo->format('M d') }}</span></div>
        <div class="card metric"><div class="icon" style="background:#eff6ff;color:#2563eb"><i data-lucide="activity"></i></div><small>Bakery Usage</small><strong>&#8369;{{ number_format($bakery, 2) }}</strong><span>{{ $chartFrom->format('M d') }} - {{ $chartTo->format('M d') }}</span></div>
        <div class="card metric"><div class="icon" style="background:#ecfdf5;color:var(--green)"><i data-lucide="calculator"></i></div><small>Total Cost</small><strong>&#8369;{{ number_format($filteredTotalCost, 2) }}</strong><span>{{ $chartFrom->format('M d') }} - {{ $chartTo->format('M d') }}</span></div>
    </section>

    <section class="grid main-side" style="margin-top:28px">
        <div class="card">
            <div class="panel-head">
                <span><i data-lucide="activity" style="width:15px;color:var(--blue)"></i> Costing Trends</span>
                <span class="muted tiny">{{ $chartFrom->format('M d, Y') }} to {{ $chartTo->format('M d, Y') }}</span>
            </div>
            <div class="chart-filter-bar">
                <div class="filter-row">
                    <div class="filter-group">
                        <span class="filter-label">Month Filter</span>
                        <form method="GET" class="filter-group">
                            <label class="filter-field">Month
                                <select name="chart_month" class="month">
                                    @foreach (range(1, 12) as $monthNumber)
                                        <option value="{{ $monthNumber }}" @selected($selectedMonth === $monthNumber)>{{ \Carbon\Carbon::create(null, $monthNumber, 1)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">Year
                                <select name="chart_year" class="year">
                                    @foreach ($availableYears as $year)
                                        <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="btn small primary"><i data-lucide="calendar"></i>Apply Month</button>
                        </form>
                        <a class="btn small ghost" href="{{ route('dashboard', ['chart_from' => now()->toDateString(), 'chart_to' => now()->toDateString()]) }}">Today</a>
                    </div>

                    <div class="filter-group">
                        <span class="filter-label">Custom Range</span>
                        <form method="GET" class="filter-group">
                            <label class="filter-field">From
                                <input type="date" name="chart_from" value="{{ $chartFrom->toDateString() }}">
                            </label>
                            <label class="filter-field">To
                                <input type="date" name="chart_to" value="{{ $chartTo->toDateString() }}">
                            </label>
                            <button class="btn small ghost"><i data-lucide="filter"></i>Apply Range</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="height:280px"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="card">
            <div class="panel-head"><span><i data-lucide="trending-up" style="width:15px;color:var(--green)"></i> Department Cost</span><span class="muted tiny">{{ $chartFrom->format('M d') }} - {{ $chartTo->format('M d') }}</span></div>
            <div class="panel-body">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span class="muted">KITCHEN</span><strong>&#8369;{{ number_format($kitchen, 2) }}</strong></div>
                <div class="progress"><span style="width:{{ ($kitchen / $total) * 100 }}%"></span></div>
                <div style="display:flex;justify-content:space-between;margin:24px 0 8px"><span class="muted">BAKERY</span><strong>&#8369;{{ number_format($bakery, 2) }}</strong></div>
                <div class="progress"><span style="width:{{ ($bakery / $total) * 100 }}%;background:#e5e7eb"></span></div>
                <div style="display:flex;justify-content:space-between;margin:24px 0 8px"><span class="muted">TOTAL</span><strong>&#8369;{{ number_format($kitchen + $bakery, 2) }}</strong></div>
                <div class="progress"><span style="width:100%;background:#10b981"></span></div>
            </div>
        </div>
    </section>

    <section class="grid two" style="margin-top:28px">
        <div class="card table-card">
            <div class="panel-head"><span><i data-lucide="activity" style="width:15px"></i> Recent Activity</span><a class="tiny" style="color:var(--blue)" href="{{ route('reports.history') }}">View All</a></div>
            <table>
                <tbody>
                    @forelse ($recentMovements->take(4) as $movement)
                        <tr>
                            <td style="width:48px"><span class="icon" style="background:{{ $movement->type === 'In' ? '#d1fae5' : '#fef3c7' }};color:{{ $movement->type === 'In' ? '#059669' : '#d97706' }};width:34px;height:34px"><i data-lucide="{{ $movement->type === 'In' ? 'arrow-up-right' : 'arrow-down-right' }}"></i></span></td>
                            <td><strong>{{ $movement->product->name }}</strong><br><span class="tiny muted">{{ $movement->type === 'In' ? 'Stock Arrival' : 'Used by '.$movement->department }} &bull; {{ $movement->created_at->format('h:i A') }}</span></td>
                            <td style="text-align:right;font-weight:900;color:{{ $movement->type === 'In' ? '#00a872' : '#ea6a00' }}">{{ $movement->type === 'In' ? '+' : '-' }}{{ $formatQuantity($movement->quantity) }} {{ $movement->product->unit }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty">No recent activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="panel-head" style="background:#fff8e6;color:#92400e"><span><i data-lucide="circle-alert" style="width:15px"></i> Critical Low Stock</span></div>
            <div class="panel-body" style="max-height:315px;overflow:auto">
                @if ($stockAlerts->isNotEmpty())
                    @foreach ($stockAlerts as $product)
                        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #eef2f7">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <div class="tiny muted">{{ $product->sku }} &bull; Threshold: {{ $formatQuantity($product->low_stock_threshold) }} {{ $product->unit }}</div>
                            </div>
                            <div style="text-align:right">
                                <span class="badge {{ $product->status === 'Out' ? 'red' : 'orange' }}">{{ $product->status === 'Out' ? 'Out' : 'Low' }}</span>
                                <div class="tiny" style="margin-top:6px;font-weight:900;color:{{ $product->status === 'Out' ? '#dc2626' : '#b45309' }}">{{ $formatQuantity($product->current_stock) }} {{ $product->unit }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty"><i data-lucide="package" style="width:34px;height:34px;color:#d1d5db"></i><br><br>All products are within safe stock levels.</div>
                @endif
            </div>
        </div>
    </section>

    <script>
        const trendCanvas = document.getElementById('trendChart');
        const trendContext = trendCanvas.getContext('2d');
        const trendGradient = trendContext.createLinearGradient(0, 0, 0, 260);
        trendGradient.addColorStop(0, 'rgba(0, 159, 107, .32)');
        trendGradient.addColorStop(1, 'rgba(0, 159, 107, 0)');

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'Total Cost',
                    data: @json($trendCosts),
                    borderColor: '#009f6b',
                    backgroundColor: trendGradient,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#009f6b',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    tension: .35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: (context) => {
                                const kitchenCosts = @json($trendKitchenCosts);
                                const bakeryCosts = @json($trendBakeryCosts);
                                const index = context.dataIndex;
                                const total = Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                const kitchen = Number(kitchenCosts[index] || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                const bakery = Number(bakeryCosts[index] || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                                return [
                                    'Total Cost: PHP ' + total,
                                    'Kitchen Cost: PHP ' + kitchen,
                                    'Bakery Cost: PHP ' + bakery
                                ];
                            }
                        }
                    }
                },
                interaction: { mode: 'nearest', intersect: false },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#eef2f7' },
                        ticks: { color: '#64748b', callback: (value) => 'PHP ' + value }
                    }
                }
            }
        });
    </script>
</x-layouts.app>
