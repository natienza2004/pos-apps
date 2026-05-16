@props(['status'])

@php
    $class = match ($status) {
        'In Stock' => 'bg-emerald-400/15 text-emerald-200 border-emerald-300/20',
        'Low' => 'bg-amber-400/15 text-amber-200 border-amber-300/20',
        default => 'bg-rose-400/15 text-rose-200 border-rose-300/20',
    };
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $class }}">{{ $status }}</span>
