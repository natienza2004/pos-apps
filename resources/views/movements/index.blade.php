<x-layouts.app title="Stock Movements | StockFlow" heading="Stock Movements">
    <section class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <form method="POST" action="{{ route('movements.store') }}" class="rounded-lg border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
            @csrf
            <h2 class="mb-4 text-lg font-semibold">Record Transaction</h2>
            <div class="grid gap-3">
                <label class="text-sm">Product
                    <select name="product_id" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }} ({{ number_format((float) $product->current_stock, 3) }} {{ $product->unit }})</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm">Type
                        <select name="type" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white">
                            <option value="In">Stock In</option>
                            <option value="Out">Stock Out</option>
                        </select>
                    </label>
                    <label class="text-sm">Quantity<input type="number" step="0.001" min="0.001" name="quantity" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white"></label>
                </div>
                <label class="text-sm">Department
                    <select name="department" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white">
                        <option>Supplier</option>
                        <option>Kitchen</option>
                        <option>Bakery</option>
                    </select>
                </label>
                <label class="text-sm">Reason<input name="reason" placeholder="PO, batch, recipe, adjustment" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white"></label>
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white">
                    <i data-lucide="repeat-2" class="size-4"></i>Record Movement
                </button>
            </div>
        </form>

        <div class="rounded-lg border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
            <h2 class="mb-4 text-lg font-semibold">Movement Ledger</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="text-slate-300">
                        <tr class="border-b border-white/10">
                            <th class="py-3">Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Department</th>
                            <th>Cost</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="py-3 text-slate-300">{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                <td class="font-medium">{{ $movement->product->name }}</td>
                                <td><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $movement->type === 'In' ? 'bg-emerald-400/15 text-emerald-200' : 'bg-amber-400/15 text-amber-200' }}">{{ $movement->type }}</span></td>
                                <td>{{ number_format((float) $movement->quantity, 3) }} {{ $movement->product->unit }}</td>
                                <td>{{ $movement->department }}</td>
                                <td>P{{ number_format($movement->cost, 2) }}</td>
                                <td class="text-slate-300">{{ $movement->reason ?: 'None' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-slate-300">No movements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $movements->links() }}</div>
        </div>
    </section>
</x-layouts.app>
