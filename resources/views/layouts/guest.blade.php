<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('components.theme-initializer')

        <title>{{ config('app.name', 'FinControl') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 px-3 py-4 sm:px-6 sm:py-8">
            <div class="absolute right-4 top-4 z-20 sm:right-6 sm:top-6">
                <x-theme-toggle />
            </div>
            <div class="pointer-events-none absolute -left-40 -top-40 h-96 w-96 rounded-full bg-indigo-200/50 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-48 -right-32 h-96 w-96 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>

            <div class="relative grid w-full max-w-md overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-2xl shadow-slate-900/10 lg:min-h-[min(820px,_calc(100vh_-_4rem))] lg:max-w-6xl lg:grid-cols-[1fr_0.9fr]">
                <aside class="relative hidden overflow-hidden bg-brand-gradient p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                    <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full border border-white/10" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-32 -left-28 h-96 w-96 rounded-full border border-white/10" aria-hidden="true"></div>
                    <div class="relative">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-primary">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path d="M4 19V5m0 14h16M8 15v-3m4 3V7m4 8v-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="text-lg font-semibold tracking-tight">FinControl</span>
                        </a>
                    </div>

                    <div class="relative my-12 max-w-lg">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Controle financeiro compartilhado</p>
                        <h2 class="mt-4 text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">
                            Mais clareza para cada compromisso.
                        </h2>
                        <p class="mt-5 max-w-md text-base leading-7 text-white/75">
                            Organize contratos recorrentes, acompanhe vencimentos e mantenha as unidades alinhadas.
                        </p>

                        <div class="mt-10 space-y-3">
                            <div class="flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="M4 6h16M4 12h10M4 18h7" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Contas organizadas por unidade</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                        <path d="M12 7v5l3 2" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Vencimentos em um só painel</span>
                            </div>
                        </div>
                    </div>

                    <p class="relative text-xs text-white/60">Acesso seguro à sua conta FinControl</p>
                </aside>

                <div class="flex items-center justify-center px-5 py-8 sm:px-8 lg:px-10 xl:px-14">
                    <div class="w-full max-w-md">
                        <div class="mb-7 flex justify-center lg:hidden">
                            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-primary text-white shadow-sm">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="M4 19V5m0 14h16M8 15v-3m4 3V7m4 8v-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="text-lg font-semibold tracking-tight text-slate-900">FinControl</span>
                            </a>
                        </div>

                        <section>
                            @isset($heading)
                                <header class="mb-7 space-y-2">
                                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $heading }}</h1>
                                    @isset($description)
                                        <p class="text-sm leading-6 text-slate-500">{{ $description }}</p>
                                    @endisset
                                </header>
                            @endisset

                            {{ $slot }}
                        </section>

                        <x-flash-notification />

                        <p class="mt-7 text-center text-xs text-slate-400 lg:hidden">
                            Acesso seguro à sua conta FinControl
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
