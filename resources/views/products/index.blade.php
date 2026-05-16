<x-layouts.app title="Products | StockFlow">
    @php
        $categories = ['Kitchen', 'Bakery', 'Raw Material', 'Packaging'];
        $units = [
            'grams' => 'Grams',
            'liters' => 'Liters',
            'kilograms' => 'Kilograms',
            'piece' => 'Piece',
            'pack' => 'Pack',
            'bags' => 'Bags',
        ];
    @endphp

    <div class="page-head">
        <div><h1>Products</h1><div class="sub">Manage your catalog, stock thresholds and costing rules.</div></div>
        <a class="btn primary" href="#add-product"><i data-lucide="plus"></i>Add Product</a>
    </div>

    <div class="card table-card">
        <div class="panel-body" style="display:flex;justify-content:space-between;gap:14px">
            <div class="search-wrap"><i data-lucide="search"></i><input class="search" style="border:1px solid #bfc8d7;border-radius:6px;width:380px;background:#fff" placeholder="Search by name or SKU..."></div>
            <form method="GET" style="display:flex;gap:10px;align-items:center">
                <i data-lucide="filter" style="width:16px;color:#64748b"></i>
                <select name="status" onchange="this.form.submit()" style="width:150px;margin:0">
                    <option value="all" @selected($selectedStatus === 'all')>All Status</option>
                    <option value="In Stock" @selected($selectedStatus === 'In Stock')>In Stock</option>
                    <option value="Low" @selected($selectedStatus === 'Low')>Low Stock</option>
                    <option value="Out" @selected($selectedStatus === 'Out')>Out of Stock</option>
                </select>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Current Stock</th>
                    <th>In Costing</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><strong>{{ $product->name }}</strong><br><span class="tiny muted">{{ $product->sku }}</span></td>
                        <td><span class="tag">{{ $product->category }}</span></td>
                        <td>{{ $product->unit }}</td>
                        <td><strong>&#8369;{{ number_format((float) $product->price, 2) }}</strong></td>
                        <td><strong>{{ number_format((float) $product->current_stock, 0) }} {{ $product->unit }}</strong></td>
                        <td>{{ $product->include_in_costing ? 'Yes' : 'No' }}</td>
                        <td><span class="badge {{ $product->status === 'In Stock' ? 'green' : ($product->status === 'Low' ? 'orange' : 'red') }}">{{ $product->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:14px;align-items:center">
                                <a class="btn small ghost" href="#edit-product-{{ $product->id }}"><i data-lucide="pencil"></i></a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn small ghost" style="color:#dc2626"><i data-lucide="trash-2"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($products as $product)
        <div id="edit-product-{{ $product->id }}" class="modal-backdrop">
            <div class="modal">
                <div class="modal-head">
                    <span>Edit Product</span>
                    <a href="#"><i data-lucide="x"></i></a>
                </div>
                <form method="POST" action="{{ route('products.update', $product) }}" class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <label>Product Name<input name="name" value="{{ $product->name }}" required></label>
                        <label>SKU<input name="sku" value="{{ $product->sku }}" required></label>
                        <label>Category
                            <select name="category" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($product->category === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Unit
                            <select name="unit" required>
                                @foreach ($units as $value => $label)
                                    <option value="{{ $value }}" @selected($product->unit === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Price per Unit<input type="number" step="0.01" min="0" name="price" value="{{ $product->price }}" required></label>
                        <label>Starting Stock<input type="number" step="0.001" min="0" name="starting_stock" value="{{ $product->starting_stock }}" required></label>
                        <label>Current Stock<input type="number" step="0.001" min="0" name="current_stock" value="{{ $product->current_stock }}" required></label>
                        <label>Low Stock Threshold<input type="number" min="0" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}" required></label>
                        <label style="border:1px solid #d9dee7;border-radius:6px;padding:12px;margin-top:20px">
                            <input type="checkbox" name="include_in_costing" value="1" @checked($product->include_in_costing) style="width:auto;min-height:auto;margin:0 8px 0 0">Include in Costing
                            <br><span class="tiny muted">Will be included in financial calculations</span>
                        </label>
                    </div>
                    <div class="form-actions">
                        <a class="btn ghost" href="#">Cancel</a>
                        <button class="btn primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div id="add-product" class="modal-backdrop">
        <div class="modal">
            <div class="modal-head">
                <span>Add New Product</span>
                <a href="#"><i data-lucide="x"></i></a>
            </div>
            <form method="POST" action="{{ route('products.store') }}" class="modal-body">
                @csrf
                <div class="form-grid">
                    <label>Product Name<input name="name" placeholder="e.g. All-Purpose Flour" required></label>
                    <label>SKU (Optional)<input name="sku" value="{{ old('sku', $nextSku) }}" placeholder="e.g. FLR-001" required></label>
                    <label>Category
                        <select name="category" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Unit
                        <select name="unit" required>
                            @foreach ($units as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Price per Unit<input type="number" step="0.01" min="0" name="price" value="0"></label>
                    <label>Starting Stock<input type="number" step="0.001" min="0" name="starting_stock" value="0"></label>
                    <label>Low Stock Threshold<input type="number" min="0" name="low_stock_threshold" value="10"></label>
                    <label style="border:1px solid #d9dee7;border-radius:6px;padding:12px;margin-top:20px"><input type="checkbox" name="include_in_costing" value="1" checked style="width:auto;min-height:auto;margin:0 8px 0 0">Include in Costing<br><span class="tiny muted">Will be included in financial calculations</span></label>
                    <label class="full">Notes (Optional)<textarea placeholder="Add storage instructions or details..."></textarea></label>
                </div>
                <div class="form-actions">
                    <a class="btn ghost" href="#">Cancel</a>
                    <button class="btn primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
