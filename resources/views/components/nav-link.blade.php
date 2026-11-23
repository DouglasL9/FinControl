@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-lg bg-brand-muted px-3 py-2 text-sm font-semibold text-brand-primary transition focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2'
            : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
