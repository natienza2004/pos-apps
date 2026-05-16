<x-layouts.app title="Costing Summary | StockFlow">
    @php
        $kPct = $totalCost > 0 ? ($kitchenCost / $totalCost) * 100 : 0;
        $bPct = $totalCost > 0 ? ($bakeryCost / $totalCost) * 100 : 0;
    @endphp
    <div class="page-head">
        <div><h1>Costing Analytics</h1><div class="sub">In-depth financial analysis of inventory consumption.</div></div>
        <form method="GET"><input type="month" name="month" value="{{ $month->format('Y-m') }}" style="width:170px;margin:0"></form>
    </div>
    <section class="grid cards-3">
        <div class="card metric"><small>Total Monthly Expenditure</small><strong style="font-size:34px;color:#4f36f5">₱{{ number_format($totalCost, 2) }}</strong><span>Consolidated across all departments</span></div>
        <div class="card metric" style="border-left-color:var(--orange)"><small>Kitchen Total</small><strong>₱{{ number_format($kitchenCost, 2) }}</strong><span>{{ number_format($kPct, 1) }}% of total cost</span></div>
        <div class="card metric" style="border-left-color:#f43f5e"><small>Bakery Total</small><strong>₱{{ number_format($bakeryCost, 2) }}</strong><span>{{ number_format($bPct, 1) }}% of total cost</span></div>
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
                        <strong class="money">₱{{ number_format($ingredient['cost'], 2) }}</strong>
                    </div>
                @empty
                    <div class="empty">No costing activity for this month.</div>
                @endforelse
                <button class="btn ghost" style="width:100%;border-style:dashed;margin-top:14px">View Detailed Breakdown <i data-lucide="arrow-right"></i></button>
            </div>
        </div>
    </section>
    <div class="audit"><div><h2 style="margin:0 0 8px">Generate Costing Audit</h2><div>Need a physical copy of your financial reports? You can generate a comprehensive PDF audit of all stock movements and their corresponding costs for your accounting team.</div></div><div style="display:flex;gap:14px"><button class="btn">Preview PDF</button><button class="btn">Download Audit</button></div></div>
    <script>
        new Chart(document.getElementById('dailyCost'), { type: 'bar', data: { labels: @json($dailyCosts->keys()), datasets: [{ data: @json($dailyCosts->values()), backgroundColor: '#f59e0b' }] }, options: { plugins: { legend: { display:false } }, scales: { x: { grid: { display:false } }, y: { beginAtZero:true, ticks: { callback: v => '₱' + v } } } } });
    </script>
</x-layouts.app>
