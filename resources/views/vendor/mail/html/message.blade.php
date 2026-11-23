@php
    $brandName = $brandName ?? config('app.name');
    $brandLogoUrl = $brandLogoUrl ?? null;
@endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
@if ($brandLogoUrl)
<img src="{{ $brandLogoUrl }}" class="brand-logo" alt="{{ $brandName }}" height="56" style="display: block; height: 56px; max-height: 56px; width: auto; max-width: 220px; border: 0; margin: 0 auto;">
@else
<span style="font-size: 26px; font-weight: 800; letter-spacing: -0.02em; color: #4f46e5;">{{ $brandName }}</span>
@endif
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ $brandName }} · © {{ date('Y') }}

Você recebeu este e-mail porque ele está associado a uma conta no {{ $brandName }}.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
