@props(['label', 'value', 'icon', 'tone' => 'indigo'])

@php
    $tones = [
        'indigo' => 'bg-indigo-500/18 text-indigo-100 border-indigo-300/20',
        'emerald' => 'bg-emerald-500/18 text-emerald-100 border-emerald-300/20',
        'amber' => 'bg-amber-500/18 text-amber-100 border-amber-300/20',
    ];
@endphp

<div class="rounded-lg border {{ $tones[$tone] }} p-5 shadow-2xl shadow-black/10 backdrop-blur-xl">
    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-slate-300">{{ $label }}</p>
        <i data-lucide="{{ $icon }}" class="size-5"></i>
    </div>
    <p class="text-3xl font-semibold text-white">{{ $value }}</p>
</div>
