<x-layouts.app title="Inventory History | StockFlow">
    @php
        $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
    @endphp

    <style>
        .history-row { cursor: pointer; }
        .history-row:hover, .history-row:focus { background: #fbfcfe; outline: none; }
        .movement-summary { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .movement-summary strong { display: block; font-size: 18px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .detail-item { border: 1px solid #e8ecf2; border-radius: 8px; padding: 12px 14px; }
        .detail-item span { display: block; color: #64748b; font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .detail-item strong { display: block; margin-top: 6px; color: #0f172a; line-height: 1.35; }
        .detail-item.full { grid-column: 1 / -1; }
        .modal-panel[hidden] { display: none; }
        .modal-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .modal-form label.full { grid-column: 1 / -1; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; padding-top: 18px; border-top: 1px solid #e8ecf2; }
        @media (max-width: 720px) {
            .detail-grid { grid-template-columns: 1fr; }
            .detail-item.full { grid-column: auto; }
            .modal-form { grid-template-columns: 1fr; }
            .modal-form label.full { grid-column: auto; }
            .movement-summary { align-items: flex-start; flex-direction: column; }
        }
    </style>

    <div class="page-head">
        <div>
            <h1>Stock Movement History</h1>
            <div class="sub">Audit trail of every stock in and stock out operation.</div>
        </div>
    </div>

    <div class="card table-card">
        <form method="GET" class="panel-body" style="display:grid;grid-template-columns:1.4fr repeat(5, minmax(140px, 1fr));gap:12px;align-items:end">
            <label>Search
                <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Product, SKU, invoice, or reference...">
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
                    <th>In Costing</th>
                    <th>Dept / Source</th>
                    <th>Invoice / Reference</th>
                    <th>Notes</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    @php
                        $included = $movement->include_in_costing ?? $movement->product->include_in_costing;
                    @endphp
                    <tr class="history-row" data-modal-target="movement-{{ $movement->id }}" tabindex="0" title="View movement details">
                        <td><strong>{{ $movement->created_at->format('M d, Y') }}</strong><br><span class="tiny muted">{{ $movement->created_at->format('h:i A') }}</span></td>
                        <td><strong>{{ $movement->product->name }}</strong><br><span class="tiny muted">{{ $movement->product->sku }}</span></td>
                        <td><span class="badge {{ $movement->type === 'In' ? 'green' : 'orange' }}">{{ $movement->type === 'In' ? 'Stock In' : 'Stock Out' }}</span></td>
                        <td style="font-weight:900;color:{{ $movement->type === 'In' ? '#00a872' : '#ea6a00' }}">{{ $movement->type === 'In' ? '+' : '-' }}{{ $formatQuantity($movement->quantity) }} {{ $movement->product->unit }}</td>
                        <td>
                            @if ($movement->type === 'In')
                                <span class="tag">N/A</span>
                            @else
                                <span class="badge {{ $included ? 'green' : 'red' }}">{{ $included ? 'Included' : 'Excluded' }}</span>
                            @endif
                        </td>
                        <td><span class="tag">{{ $movement->department }}</span></td>
                        <td class="muted">{{ $movement->reason ?: 'N/A' }}</td>
                        <td class="muted">{{ $movement->notes ?: ($movement->type === 'Out' ? ($movement->reason ?: 'N/A') : 'N/A') }}</td>
                        <td><i data-lucide="user" style="width:13px"></i> {{ $movement->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty">No movements match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        @foreach ($movements as $movement)
            @php
                $included = $movement->include_in_costing ?? $movement->product->include_in_costing;
            @endphp
            <div id="movement-{{ $movement->id }}" class="modal-backdrop">
                <div class="modal">
                    <div class="modal-head">
                        <span>Movement Details</span>
                        <a class="btn small ghost" href="#" aria-label="Close movement details"><i data-lucide="x"></i></a>
                    </div>
                    <div class="modal-body">
                        <div class="modal-panel" data-detail-panel>
                            <div class="movement-summary">
                                <div>
                                    <strong>{{ $movement->product->name }}</strong>
                                    <span class="muted">{{ $movement->product->sku }} &bull; {{ $movement->product->category }}</span>
                                </div>
                                <span class="badge {{ $movement->type === 'In' ? 'green' : 'orange' }}">{{ $movement->type === 'In' ? 'Stock In' : 'Stock Out' }}</span>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span>Date & Time</span>
                                    <strong>{{ $movement->created_at->format('M d, Y h:i A') }}</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Quantity</span>
                                    <strong style="color:{{ $movement->type === 'In' ? '#00a872' : '#ea6a00' }}">{{ $movement->type === 'In' ? '+' : '-' }}{{ $formatQuantity($movement->quantity) }} {{ $movement->product->unit }}</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Department / Source</span>
                                    <strong>{{ $movement->department }}</strong>
                                </div>
                                <div class="detail-item">
                                    <span>User</span>
                                    <strong>{{ $movement->user->name }}</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Unit Price</span>
                                    <strong>&#8369;{{ number_format((float) ($movement->unit_price ?? $movement->product->price), 2) }}</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Costing</span>
                                    <strong>
                                        @if ($movement->type === 'In')
                                            N/A
                                        @else
                                            {{ $included ? 'Included' : 'Excluded' }} &bull; &#8369;{{ number_format($movement->cost, 2) }}
                                        @endif
                                    </strong>
                                </div>
                                <div class="detail-item full">
                                    <span>{{ $movement->type === 'In' ? 'Invoice / Ref Number' : 'Reference / Notes' }}</span>
                                    <strong>{{ $movement->reason ?: 'N/A' }}</strong>
                                </div>
                                <div class="detail-item full">
                                    <span>Notes</span>
                                    <strong>{{ $movement->notes ?: ($movement->type === 'Out' ? ($movement->reason ?: 'N/A') : 'N/A') }}</strong>
                                </div>
                            </div>

                            <div class="modal-actions">
                                <a class="btn ghost" href="#">Close</a>
                                <button type="button" class="btn primary" data-edit-movement><i data-lucide="pencil"></i>Edit</button>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('movements.update', $movement) }}" class="modal-panel modal-form" data-edit-panel hidden>
                            @csrf
                            @method('PUT')
                            <label>Product
                                <select name="product_id" required>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected($movement->product_id === $product->id)>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Date
                                <input type="date" name="movement_date" value="{{ $movement->created_at->format('Y-m-d') }}" required>
                            </label>
                            <label>Quantity
                                <input type="number" name="quantity" step="0.001" min="0.001" value="{{ (float) $movement->quantity }}" required>
                            </label>
                            <label>{{ $movement->type === 'In' ? 'Supplier / Source' : 'Department' }}
                                @if ($movement->type === 'Out')
                                    <select name="department" required>
                                        <option value="Kitchen" @selected($movement->department === 'Kitchen')>Kitchen</option>
                                        <option value="Bakery" @selected($movement->department === 'Bakery')>Bakery</option>
                                        @if (! in_array($movement->department, ['Kitchen', 'Bakery'], true))
                                            <option value="{{ $movement->department }}" selected>{{ $movement->department }}</option>
                                        @endif
                                    </select>
                                @else
                                    <input name="department" value="{{ $movement->department }}" required>
                                @endif
                            </label>
                            <label class="full">{{ $movement->type === 'In' ? 'Invoice / Ref Number' : 'Reference' }}
                                <input name="reason" value="{{ $movement->reason }}">
                            </label>
                            @if ($movement->type === 'Out')
                                <label class="full" style="border:1px solid #d9dee7;border-radius:6px;padding:12px">
                                    <input type="hidden" name="include_in_costing" value="0">
                                    <input type="checkbox" name="include_in_costing" value="1" @checked($included) style="width:auto;min-height:auto;margin:0 8px 0 0">Include this usage in costing
                                </label>
                            @endif
                            <label class="full">Notes
                                <textarea name="notes" placeholder="Movement notes...">{{ $movement->notes ?: ($movement->type === 'Out' ? $movement->reason : '') }}</textarea>
                            </label>
                            <div class="modal-actions full">
                                <button type="button" class="btn ghost" data-cancel-edit>Cancel</button>
                                <button class="btn primary"><i data-lucide="save"></i>Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top:16px">{{ $movements->links() }}</div>

    <script>
        const resetMovementModal = (modal) => {
            modal?.querySelector('[data-detail-panel]')?.removeAttribute('hidden');
            modal?.querySelector('[data-edit-panel]')?.setAttribute('hidden', '');
        };

        document.querySelectorAll('[data-modal-target]').forEach((row) => {
            row.addEventListener('click', () => {
                resetMovementModal(document.getElementById(row.dataset.modalTarget));
                window.location.hash = row.dataset.modalTarget;
            });

            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    resetMovementModal(document.getElementById(row.dataset.modalTarget));
                    window.location.hash = row.dataset.modalTarget;
                }
            });
        });

        document.querySelectorAll('.modal-backdrop').forEach((modal) => {
            modal.querySelector('[data-edit-movement]')?.addEventListener('click', () => {
                modal.querySelector('[data-detail-panel]')?.setAttribute('hidden', '');
                modal.querySelector('[data-edit-panel]')?.removeAttribute('hidden');
            });

            modal.querySelector('[data-cancel-edit]')?.addEventListener('click', () => {
                resetMovementModal(modal);
            });
        });
    </script>
</x-layouts.app>
