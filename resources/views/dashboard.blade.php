<x-app-layout>
    <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-28 right-1/3 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-white/75">
                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                        Painel financeiro
                    </p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Olá, {{ Auth::user()->name }}
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">
                        @if ($company)
                            Acompanhe os compromissos financeiros de {{ $company->name }} em um só lugar.
                        @else
                            Organize os compromissos financeiros da sua empresa em um só lugar.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-medium text-white backdrop-blur-sm">
                        <svg class="h-4 w-4 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <rect x="3.5" y="5" width="17" height="15.5" rx="2" stroke-width="1.7" />
                            <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                        {{ now()->format('m / Y') }}
                    </div>
                    @if ($company && $canViewPayables)
                        <a href="{{ route('payables.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-brand-primary shadow-sm transition hover:bg-white/90 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-primary">
                            Contas a pagar
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.69L10.72 6.28a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if ($company === null)
            <section class="mx-auto grid max-w-4xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm md:grid-cols-[1fr_auto]">
                <div class="p-6 sm:p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-muted text-brand-primary">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" stroke-width="2.2" stroke-linecap="round" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-xl font-semibold tracking-tight text-slate-900">Comece pelo espaço da sua empresa</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                        Cadastre a empresa para controlar contratos, unidades e contas a pagar. Depois, você poderá convidar sua equipe.
                    </p>
                </div>

                <div class="border-t border-slate-100 bg-slate-50/80 p-6 md:w-80 md:border-l md:border-t-0 sm:p-8">
                    <form method="POST" action="{{ route('companies.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="company_name" value="Nome da empresa" />
                            <x-text-input id="company_name" name="name" class="mt-1" required maxlength="255" />
                            <x-input-error :messages="$errors->getBag('companyCreation')->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button class="w-full justify-center">Criar empresa</x-primary-button>
                    </form>
                </div>
            </section>
        @elseif (! $hasDashboardAccess)
            <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-950">
                <h2 class="font-semibold">Acesso ao painel não habilitado</h2>
                <p class="mt-1 text-sm">Peça ao proprietário ou administrador da empresa para atualizar seu perfil de acesso.</p>
            </section>
        @else
            <section aria-label="Resumo de contas a pagar" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total em aberto', 'value' => $summary['open_total'], 'tone' => 'indigo'],
                    ['label' => 'A vencer', 'value' => $summary['pending_total'], 'tone' => 'amber'],
                    ['label' => 'Pagas neste mês', 'value' => $summary['paid_this_month'], 'tone' => 'emerald'],
                    ['label' => 'Em atraso', 'value' => $summary['overdue_total'], 'tone' => 'rose'],
                ] as $card)
                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                        <div @class([
                            'absolute inset-x-0 top-0 h-1',
                            'bg-brand-primary' => $card['tone'] === 'indigo',
                            'bg-amber-400' => $card['tone'] === 'amber',
                            'bg-emerald-500' => $card['tone'] === 'emerald',
                            'bg-rose-500' => $card['tone'] === 'rose',
                        ])></div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
                                    R$ {{ number_format((float) $card['value'], 2, ',', '.') }}
                                </p>
                            </div>
                            <span @class([
                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                                'bg-brand-muted text-brand-primary' => $card['tone'] === 'indigo',
                                'bg-amber-50 text-amber-600' => $card['tone'] === 'amber',
                                'bg-emerald-50 text-emerald-600' => $card['tone'] === 'emerald',
                                'bg-rose-50 text-rose-600' => $card['tone'] === 'rose',
                            ])>
                                @if ($card['tone'] === 'indigo')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="M12 2v20m5-15H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                @elseif ($card['tone'] === 'amber')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                        <path d="M12 7v5l3 2" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                @elseif ($card['tone'] === 'emerald')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="m5 12 4 4L19 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="9" stroke-width="1.6" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="M12 3 2.8 19a1.4 1.4 0 0 0 1.2 2.1h16a1.4 1.4 0 0 0 1.2-2.1L12 3Z" stroke-width="1.7" stroke-linejoin="round" />
                                        <path d="M12 9v4m0 3h.01" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                @endif
                            </span>
                        </div>
                    </article>
                @endforeach
            </section>

            <div @class(['grid gap-6', 'xl:grid-cols-3' => $unitTotals->isNotEmpty()])>
                <section @class(['overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm', 'xl:col-span-2' => $unitTotals->isNotEmpty()])>
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Acompanhe de perto</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Próximos vencimentos</h2>
                            <p class="mt-1 text-sm text-slate-500">Contas pendentes das unidades da empresa.</p>
                        </div>
                        @if ($canViewPayables)
                            <a href="{{ route('payables.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-soft hover:bg-brand-muted hover:text-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                                Ver todas
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.69L10.72 6.28a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    @if ($upcoming->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <rect x="3.5" y="5" width="17" height="15.5" rx="2" stroke-width="1.7" />
                                    <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" stroke-width="1.7" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="mt-4 font-semibold text-slate-800">Nenhum vencimento próximo</p>
                            <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">Cadastre um contrato recorrente para começar a acompanhar as contas.</p>
                            <a href="{{ route('payables.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary hover:brightness-90">
                                Cadastrar contrato <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($upcoming as $payable)
                                <li class="flex flex-col gap-4 px-5 py-4 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl border border-slate-100 bg-slate-50">
                                            <span class="text-lg font-semibold leading-none text-slate-800">{{ $payable->due_date->format('d') }}</span>
                                            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $payable->due_date->format('m/Y') }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $payable->description }}</p>
                                            <p class="mt-1 truncate text-sm text-slate-500">
                                                {{ $payable->provider }} <span class="px-1 text-slate-300">·</span> {{ $payable->unit->name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 pl-[4.5rem] sm:justify-end sm:pl-0">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-amber-50 text-amber-700' => $payable->due_date->isToday(),
                                            'bg-brand-muted text-brand-primary' => ! $payable->due_date->isToday(),
                                        ])>
                                            {{ $payable->due_date->isToday() ? 'Vence hoje' : 'Pendente' }}
                                        </span>
                                        <span class="whitespace-nowrap text-sm font-semibold text-slate-900">
                                            R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if ($unitTotals->isNotEmpty())
                    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Visão consolidada</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Totais por unidade</h2>
                            <p class="mt-1 text-sm text-slate-500">Valores em aberto, pagos e em atraso.</p>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($unitTotals as $unit)
                                <li class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="truncate text-sm font-semibold text-slate-800">{{ $unit->unit }}</h3>
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-500">
                                            {{ $unit->contract_count }} {{ \Illuminate\Support\Str::plural('contrato', (int) $unit->contract_count) }}
                                        </span>
                                    </div>
                                    <dl class="mt-3 grid grid-cols-3 gap-2">
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Em aberto</dt>
                                            <dd class="mt-1 truncate text-xs font-semibold text-slate-700">R$ {{ number_format((float) $unit->open_total, 2, ',', '.') }}</dd>
                                        </div>
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Pagas/mês</dt>
                                            <dd class="mt-1 truncate text-xs font-semibold text-slate-700">R$ {{ number_format((float) $unit->paid_this_month, 2, ',', '.') }}</dd>
                                        </div>
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Em atraso</dt>
                                            <dd @class([
                                                'mt-1 truncate text-xs font-semibold',
                                                'text-rose-700' => (float) $unit->overdue_total > 0,
                                                'text-slate-500' => (float) $unit->overdue_total <= 0,
                                            ])>R$ {{ number_format((float) $unit->overdue_total, 2, ',', '.') }}</dd>
                                        </div>
                                    </dl>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
