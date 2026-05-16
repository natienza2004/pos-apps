<x-layouts.app title="Stock Out | StockFlow">
    <div class="page-head" style="max-width:840px;margin-left:auto;margin-right:auto"><div><h1>Stock Out / Usage</h1><div class="sub">Record daily usage for Kitchen or Bakery departments.</div></div></div>
    <section class="grid" style="grid-template-columns:540px 250px;align-items:start;justify-content:center">
        <form method="POST" action="{{ route('movements.store') }}" class="card form-card" style="margin:0">
            @csrf
            <input type="hidden" name="type" value="Out">
            <label class="full">Select Product
                <select name="product_id" required><option value="">Choose a product...</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->current_stock }} {{ $product->unit }})</option>@endforeach</select>
            </label>
            <div class="form-grid" style="margin-top:22px">
                <label>Usage Date<input type="date" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required></label>
                <label>Department<select name="department"><option>Kitchen</option><option>Bakery</option></select></label>
                <label class="full">Quantity Used<input type="number" name="quantity" step="0.001" min="0.001" placeholder="0.00" required></label>
                <label class="full">Usage Notes<textarea name="reason" placeholder="e.g. Batch #45 standard usage, spilled 200g..."></textarea></label>
            </div>
            <div class="form-actions"><a class="btn ghost" href="{{ route('dashboard') }}">Cancel</a><button class="btn primary"><i data-lucide="circle-check"></i>Confirm Usage</button></div>
        </form>
        <div>
            <div class="tip danger-tip"><strong><i data-lucide="utensils"></i> Kitchen vs Bakery</strong><br>Usage is tracked separately to calculate department-specific costs. General usage will reduce stock but can be excluded from summaries.</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:24px">
                <div class="card panel-body" style="text-align:center"><i data-lucide="utensils"></i><br><strong class="tiny">KITCHEN</strong></div>
                <div class="card panel-body" style="text-align:center"><i data-lucide="wheat"></i><br><strong class="tiny">BAKERY</strong></div>
            </div>
        </div>
    </section>
</x-layouts.app>
