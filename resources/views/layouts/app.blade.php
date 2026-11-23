<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('components.theme-initializer')

        @php($currentCompany = Auth::user()?->currentCompany())
        @php($companyLogoUrl = $currentCompany?->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($currentCompany->logo_path) : null)
        <title>{{ $currentCompany?->name ? $currentCompany->name.' · ' : '' }}{{ config('app.name', 'FinControl') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            class="min-h-screen bg-slate-50"
            style="--company-primary: {{ $currentCompany?->primary_color ?? '#4f46e5' }}; --company-accent: {{ $currentCompany?->accent_color ?? '#7c3aed' }};"
        >
            @if ($companyLogoUrl && $currentCompany->watermark_opacity > 0)
                <div class="pointer-events-none fixed inset-0 z-0 flex items-center justify-center overflow-hidden" aria-hidden="true">
                    <img
                        src="{{ $companyLogoUrl }}"
                        alt=""
                        class="h-[45vh] w-[45vw] max-h-[28rem] max-w-[28rem] select-none object-contain"
                        style="opacity: {{ $currentCompany->watermark_opacity / 100 }};"
                    >
                </div>
            @endif

            <div class="relative z-10">
                @include('layouts.navigation', ['currentCompany' => $currentCompany, 'companyLogoUrl' => $companyLogoUrl])

                @isset($header)
                    <header class="border-b border-slate-200/80 bg-white">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>

                <x-flash-notification />
            </div>
        </div>
    </body>
</html>
