<x-layouts.app title="Settings | StockFlow" heading="Settings">
    <section class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('settings.update') }}" class="rounded-lg border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
            @csrf
            <h2 class="mb-4 text-lg font-semibold">Company Information</h2>
            <div class="grid gap-3">
                <label class="text-sm">Company Name<input name="company_name" value="{{ $companyName }}" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white"></label>
                <label class="text-sm">Address<textarea name="company_address" rows="4" class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/55 px-3 py-2 text-white">{{ $companyAddress }}</textarea></label>
                <button class="inline-flex w-fit items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="save" class="size-4"></i>Save Settings</button>
            </div>
        </form>

        <div class="rounded-lg border border-rose-300/20 bg-rose-500/10 p-5 backdrop-blur-xl">
            <h2 class="mb-2 text-lg font-semibold">Demo Reset</h2>
            <p class="mb-4 text-sm text-rose-100">Clear products, stock movements, and reconciliation snapshots while keeping system settings.</p>
            <form method="POST" action="{{ route('settings.reset') }}" onsubmit="return confirm('Clear all inventory data? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button class="inline-flex items-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="trash-2" class="size-4"></i>Clear Inventory Data</button>
            </form>
        </div>
    </section>
</x-layouts.app>
