<x-layouts.app title="Costing Summary | StockFlow">
    @php
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
        $kPct = $totalCost > 0 ? ($kitchenCost / $totalCost) * 100 : 0;
        $bPct = $totalCost > 0 ? ($bakeryCost / $totalCost) * 100 : 0;
        $breakdownPages = $breakdown->chunk(8)->values();
    @endphp

    <div class="page-head">
        <div><h1>Costing Analytics</h1><div class="sub">In-depth financial analysis of inventory consumption.</div></div>
        <form method="GET" style="display:flex;gap:10px;align-items:center">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" data-auto-submit style="width:170px;margin:0">
            <button class="btn primary"><i data-lucide="calendar"></i>Apply Month</button>
        </form>
    </div>

    <section class="grid cards-3">
        <div class="card metric"><small>Total Monthly Expenditure</small><strong style="font-size:34px;color:#4f36f5">&#8369;{{ number_format($totalCost, 2) }}</strong><span>Consolidated across all departments</span></div>
        <div class="card metric" style="border-left-color:var(--orange)"><small>Kitchen Total</small><strong>&#8369;{{ number_format($kitchenCost, 2) }}</strong><span>{{ number_format($kPct, 1) }}% of total cost</span></div>
        <div class="card metric" style="border-left-color:#f43f5e"><small>Bakery Total</small><strong>&#8369;{{ number_format($bakeryCost, 2) }}</strong><span>{{ number_format($bPct, 1) }}% of total cost</span></div>
    </section>

    <section class="grid two" style="margin-top:28px">
        <div class="card">
            <div class="panel-head"><span><i data-lucide="trending-up" style="width:15px;color:var(--blue)"></i> Daily Cost Distribution</span></div>
            <div class="panel-body"><canvas id="dailyCost" height="220"></canvas></div>
        </div>
        <div class="card">
            <div class="panel-head"><span><i data-lucide="filter" style="width:15px;color:var(--blue)"></i> Top High-Cost Ingredients</span></div>
            <div class="panel-body">
                @forelse ($topIngredients as $index => $ingredient)
                    <div style="display:flex;gap:14px;align-items:center;margin-bottom:20px">
                        <span class="tag" style="border-radius:999px">{{ $index + 1 }}</span>
                        <div style="flex:1"><strong>{{ $ingredient['name'] }}</strong><div class="progress" style="margin-top:8px"><span style="width:100%;background:#6554ff"></span></div></div>
                        <strong class="money">&#8369;{{ number_format($ingredient['cost'], 2) }}</strong>
                    </div>
                @empty
                    <div class="empty">No costing activity for this month.</div>
                @endforelse
                <a class="btn ghost" href="#costing-breakdown" style="width:100%;border-style:dashed;margin-top:14px">View Detailed Breakdown <i data-lucide="arrow-right"></i></a>
            </div>
        </div>
    </section>

    <div id="costing-breakdown" class="modal-backdrop">
        <div class="modal" style="width:900px">
            <div class="modal-head">
                <span>Detailed Costing Breakdown</span>
                <a class="btn small ghost" href="#" aria-label="Close detailed breakdown"><i data-lucide="x"></i></a>
            </div>
            <div class="modal-body">
                <table>
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Total Qty</th>
                            <th>Kitchen Qty</th>
                            <th>Bakery Qty</th>
                            <th>Total Cost</th>
                            <th>% of Cost</th>
                        </tr>
                    </thead>
                    @forelse ($breakdownPages as $pageIndex => $pageRows)
                        <tbody data-breakdown-page="{{ $pageIndex }}" @if ($pageIndex > 0) hidden @endif>
                            @foreach ($pageRows as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong><br><span class="tiny muted">{{ $row['sku'] }}</span></td>
                                    <td>{{ $formatQuantity($row['quantity']) }} {{ $row['unit'] }}</td>
                                    <td>{{ $formatQuantity($row['kitchen_quantity']) }} {{ $row['unit'] }}</td>
                                    <td>{{ $formatQuantity($row['bakery_quantity']) }} {{ $row['unit'] }}</td>
                                    <td class="money">&#8369;{{ number_format($row['cost'], 2) }}</td>
                                    <td>{{ number_format($row['percent'], 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="6" class="empty">No costing activity for this month.</td></tr>
                        </tbody>
                    @endforelse
                </table>
                @if ($breakdownPages->count() > 1)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px">
                        <button type="button" class="btn ghost" data-breakdown-prev><i data-lucide="chevron-left"></i>Previous</button>
                        <span class="muted tiny" data-breakdown-status>Page 1 of {{ $breakdownPages->count() }}</span>
                        <button type="button" class="btn ghost" data-breakdown-next>Next<i data-lucide="chevron-right"></i></button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('dailyCost'), { type: 'bar', data: { labels: @json($dailyCosts->keys()), datasets: [{ data: @json($dailyCosts->values()), backgroundColor: '#f59e0b' }] }, options: { plugins: { legend: { display:false } }, scales: { x: { grid: { display:false } }, y: { beginAtZero:true, ticks: { callback: v => 'PHP ' + v } } } } });

        const breakdownModal = document.getElementById('costing-breakdown');
        const breakdownPages = breakdownModal ? Array.from(breakdownModal.querySelectorAll('[data-breakdown-page]')) : [];
        let breakdownPage = 0;

        const showBreakdownPage = (page) => {
            if (!breakdownPages.length) {
                return;
            }

            breakdownPage = Math.max(0, Math.min(page, breakdownPages.length - 1));
            breakdownPages.forEach((pageBody, index) => pageBody.hidden = index !== breakdownPage);

            const status = breakdownModal.querySelector('[data-breakdown-status]');
            if (status) {
                status.textContent = `Page ${breakdownPage + 1} of ${breakdownPages.length}`;
            }

            breakdownModal.querySelector('[data-breakdown-prev]')?.toggleAttribute('disabled', breakdownPage === 0);
            breakdownModal.querySelector('[data-breakdown-next]')?.toggleAttribute('disabled', breakdownPage === breakdownPages.length - 1);
        };

        breakdownModal?.querySelector('[data-breakdown-prev]')?.addEventListener('click', () => showBreakdownPage(breakdownPage - 1));
        breakdownModal?.querySelector('[data-breakdown-next]')?.addEventListener('click', () => showBreakdownPage(breakdownPage + 1));
        showBreakdownPage(0);
    </script>
</x-layouts.app>
