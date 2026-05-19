<x-layouts.app title="Stock In | StockFlow">
    @php
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
    @endphp

    <div class="page-head" style="max-width:840px;margin-left:auto;margin-right:auto"><div><h1>Stock In</h1><div class="sub">Record new inventory arrival and update stock levels.</div></div></div>
    <section class="grid" style="grid-template-columns:540px 250px;align-items:start;justify-content:center">
        <form method="POST" action="{{ route('movements.store') }}" class="card form-card" style="margin:0">
            @csrf
            <input type="hidden" name="type" value="In">
            <label class="full">Select Product
                <select name="product_id" required><option value="">Choose a product...</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $formatQuantity($product->current_stock) }} {{ $product->unit }})</option>@endforeach</select>
            </label>
            <div class="form-grid" style="margin-top:22px">
                <label>Arrival Date<input type="date" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required></label>
                <label>Quantity Received<input type="number" name="quantity" step="0.001" min="0.001" placeholder="0" required></label>
                <label>Supplier (Optional)<input name="department" value="{{ old('department', 'Supplier') }}" placeholder="e.g. Fresh Mart Inc." required></label>
                <label>Ref / Invoice Number<input name="reason" value="{{ old('reason') }}" placeholder="e.g. INV-2024-001"></label>
                <label class="full">Notes<textarea name="notes" placeholder="Condition of goods, special instructions...">{{ old('notes') }}</textarea></label>
            </div>
            <div class="form-actions"><a class="btn ghost" href="{{ route('dashboard') }}">Cancel</a><button class="btn primary"><i data-lucide="circle-plus"></i>Save Stock In</button></div>
        </form>
        <div class="tip"><strong><i data-lucide="circle-help"></i> Quick Tip</strong><br>Recording Stock In automatically updates your "Ending Stock" and "Starting Stock" for the day. Ensure invoice numbers are accurate for historical tracking.</div>
    </section>
</x-layouts.app>
