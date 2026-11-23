<x-app-layout>
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Configurações da conta</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Meu perfil</h1>
                <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">Gerencie seus dados pessoais e mantenha sua conta protegida.</p>
            </div>
        </header>

        <section aria-label="Resumo da conta" class="flex flex-col gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-muted text-xl font-semibold text-brand-primary shadow-inner">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="mt-1 truncate text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            @if ($user->hasVerifiedEmail())
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    E-mail verificado
                </span>
            @else
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    E-mail não verificado
                </span>
            @endif
        </section>

        <section class="flex flex-col gap-4 rounded-3xl border border-brand-soft bg-brand-muted p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-brand-primary shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M12 17h.01M12 14a3 3 0 1 0-3-3m3 3v-1m0 8a9 9 0 1 0-9-9 9 9 0 0 0 9 9Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <div>
                    <h2 class="font-semibold text-slate-900">Ajuda e documentação</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Consulte orientações para organizar empresas, unidades, contratos e pagamentos.</p>
                </div>
            </div>
            <a href="{{ route('help.index') }}" class="inline-flex w-fit shrink-0 items-center gap-2 rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                Abrir guia
                <span aria-hidden="true">→</span>
            </a>
        </section>

        <nav aria-label="Seções do perfil" class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200/80 bg-white p-2 text-sm font-medium shadow-sm">
            @foreach (['dados' => 'Dados pessoais', 'seguranca' => 'Segurança', 'empresas' => 'Minhas empresas', 'conta' => 'Zona de risco'] as $anchor => $label)
                <a href="#{{ $anchor }}" class="shrink-0 rounded-xl px-4 py-2 text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="grid gap-6 xl:grid-cols-2">
            <section id="dados" class="scroll-mt-24 h-fit rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section id="seguranca" class="scroll-mt-24 h-fit space-y-6">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    @include('profile.partials.update-password-form')
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    @include('profile.partials.login-verification-info')
                </div>
            </section>

            <section id="empresas" class="scroll-mt-24 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7 xl:col-span-2">
                @include('profile.partials.companies-list')
            </section>

            <section id="conta" class="scroll-mt-24 rounded-3xl border border-rose-200/80 bg-rose-50/50 p-5 shadow-sm sm:p-7 xl:col-span-2">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-app-layout>
