@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-brand-muted px-3 py-2 text-start text-sm font-semibold text-brand-primary transition focus:outline-none focus:ring-2 focus:ring-brand-primary'
            : 'block w-full rounded-lg px-3 py-2 text-start text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-primary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
