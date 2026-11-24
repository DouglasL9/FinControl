<x-app-layout>
    <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header
            class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"
                aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Gestão financeira</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Contas a pagar</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">
                        Acompanhe contratos, vencimentos e pagamentos de {{ $company->name }}.
                    </p>
                </div>
                @if ($access['view_company'])
                    <a href="{{ route('company.show') }}"
                        class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-primary">
                        Configurações
                        <span aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </header>

        <section aria-label="Resumo financeiro" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['label' => 'Total em aberto', 'value' => $summary['open_total'], 'tone' => 'indigo'], ['label' => 'A vencer', 'value' => $summary['pending_total'], 'tone' => 'amber'], ['label' => 'Pagas neste mês', 'value' => $summary['paid_this_month'], 'tone' => 'emerald'], ['label' => 'Em atraso', 'value' => $summary['overdue_total'], 'tone' => 'rose']] as $card)
                <article
                    class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
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
                            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">R$
                                {{ number_format((float) $card['value'], 2, ',', '.') }}</p>
                        </div>
                        <span @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                            'bg-brand-muted text-brand-primary' => $card['tone'] === 'indigo',
                            'bg-amber-50 text-amber-600' => $card['tone'] === 'amber',
                            'bg-emerald-50 text-emerald-600' => $card['tone'] === 'emerald',
                            'bg-rose-50 text-rose-600' => $card['tone'] === 'rose',
                        ])>
                            @if ($card['tone'] === 'indigo')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    aria-hidden="true">
                                    <path d="M12 2v20m5-15H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                                        stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            @elseif ($card['tone'] === 'amber')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                    <path d="M12 7v5l3 2" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            @elseif ($card['tone'] === 'emerald')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <circle cx="12" cy="12" r="9" stroke-width="1.6" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    aria-hidden="true">
                                    <path d="M12 3 2.8 19a1.4 1.4 0 0 0 1.2 2.1h16a1.4 1.4 0 0 0 1.2-2.1L12 3Z"
                                        stroke-width="1.7" stroke-linejoin="round" />
                                    <path d="M12 9v4m0 3h.01" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            @endif
                        </span>
                    </div>
                </article>
            @endforeach
        </section>

        @if ($unitTotals->isNotEmpty())
            <section aria-label="Resumo por unidade">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Resumo por unidade</h2>
                        <p class="mt-1 text-sm text-slate-500">Compare os valores em aberto, a vencer, pagos e em
                            atraso.</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($unitTotals as $unit)
                        <article
                            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-sm font-semibold text-brand-primary">
                                    {{ mb_strtoupper(mb_substr($unit->unit, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-slate-800">{{ $unit->unit }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $unit->contract_count }}
                                        {{ \Illuminate\Support\Str::plural('contrato', (int) $unit->contract_count) }}
                                    </p>
                                </div>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-4">
                                <div>
                                    <dt class="text-xs text-slate-500">Em aberto</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-800">R$
                                        {{ number_format((float) $unit->open_total, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">A vencer</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-800">R$
                                        {{ number_format((float) $unit->pending_total, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Pagas neste mês</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-800">R$
                                        {{ number_format((float) $unit->paid_this_month, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Em atraso</dt>
                                    <dd @class([
                                        'mt-1 text-sm font-semibold',
                                        'text-rose-700' => (float) $unit->overdue_total > 0,
                                        'text-slate-500' => (float) $unit->overdue_total <= 0,
                                    ])>R$
                                        {{ number_format((float) $unit->overdue_total, 2, ',', '.') }}</dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-12">
                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-4">
<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="font-semibold text-slate-900">Contratos e parcelas</h2>
                                <p class="mt-1 text-sm text-slate-500">As parcelas ficam agrupadas por contrato;
                                    expanda para ver os detalhes.</p>
                            </div>
@if ($access['create_contract'])
    @php
        $openContractModal = $errors->hasAny(['description', 'supplier_id', 'unit_id', 'due_day', 'starts_on', 'ends_on']) || ($errors->has('amount') && old('due_day') !== null);
    @endphp
    <div x-data x-init="@js($openContractModal) && $nextTick(() => $refs.newContract.showModal())">
        <button type="button" @click="$refs.newContract.showModal()"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 sm:w-auto">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" />
            </svg>
            Novo contrato
        </button>

        <dialog x-ref="newContract" aria-modal="true" aria-labelledby="new-contract-title"
            @click.self="$refs.newContract.close()"
            class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-3xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="new-contract-title" class="font-semibold text-slate-900">Novo contrato mensal</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">As contas dos próximos 3 meses serão criadas automaticamente.</p>
                </div>
                <button type="button" @click="$refs.newContract.close()" aria-label="Fechar"
                    class="shrink-0 rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-primary">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-3.72 3.72 3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 0 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                    </svg>
                </button>
            </div>
                    <form method="POST" action="{{ route('payables.contracts.store') }}" class="space-y-4 p-5 sm:p-6">
                        @csrf
                        <div>
                            <x-input-label for="description" value="Descrição do serviço" />
                            <x-text-input id="description" name="description" class="mt-1"
                                placeholder="Ex.: Internet da sede" required maxlength="255" />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        @if ($availableSuppliers->isEmpty())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-sm font-medium text-amber-900">Cadastre ou reative um fornecedor antes de
                                    criar contratos.</p>
                                <a href="{{ route('company.show') }}#suppliers"
                                    class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:brightness-90">Gerenciar
                                    fornecedores</a>
                            </div>
                        @else
                            <div>
                                <x-input-label for="supplier_id" value="Fornecedor" />
                                <select id="supplier_id" name="supplier_id" required
                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                    <option value="">Selecione um fornecedor</option>
                                    @foreach ($availableSuppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                            {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                            </div>
                        @endif
                        @if ($availableUnits->isEmpty())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-sm font-medium text-amber-900">Cadastre ou reative uma unidade antes de
                                    criar contratos.</p>
                                <a href="{{ route('company.show') }}#units"
                                    class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:brightness-90">Cadastrar
                                    unidade</a>
                            </div>
                        @else
                            <div>
                                <x-input-label for="unit_id" value="Unidade / sede" />
                                <select id="unit_id" name="unit_id" required
                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                    <option value="">Selecione uma unidade</option>
                                    @foreach ($availableUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                            {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                            </div>
                        @endif
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="amount" value="Valor mensal (R$)" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01"
                                    min="0.01" class="mt-1" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="due_day" value="Dia do vencimento" />
                                <x-text-input id="due_day" name="due_day" type="number" min="1"
                                    max="31" class="mt-1" required />
                                <x-input-error :messages="$errors->get('due_day')" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="starts_on" value="Início do contrato" />
                                <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1"
                                    required />
                                <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="ends_on" value="Término (opcional)" />
                                <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1" />
                                <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                            </div>
                        </div>
                        <p class="text-xs leading-5 text-slate-500">O valor de cada conta gerada pode ser ajustado
                            individualmente.</p>
                        @if ($availableUnits->isNotEmpty() && $availableSuppliers->isNotEmpty())
                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
<x-secondary-button type="button" @click="$refs.newContract.close()">Cancelar</x-secondary-button>
<x-primary-button class="justify-center">Cadastrar contrato</x-primary-button>
</div>
                        @endif
                    </form>
        </dialog>
    </div>
@endif
</div>
                            <form method="GET" action="{{ route('payables.index') }}"
                                class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                <label class="sr-only" for="search">Buscar por fornecedor, unidade ou
                                    descrição</label>
                                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                                    placeholder="Buscar contrato..."
                                    class="min-w-0 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                <label class="sr-only" for="status">Filtrar por status</label>
                                <select id="status" name="status"
                                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Todos os status</option>
                                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>A vencer</option>
                                    <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>Em atraso</option>
                                    <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>Pagas</option>
                                </select>
                                <label class="sr-only" for="filter_unit_id">Filtrar por sede</label>
                                <select id="filter_unit_id" name="unit_id"
                                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                    <option value="">Todas as sedes</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>
                                            {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                <x-secondary-button type="submit"
                                    class="w-fit justify-center">Filtrar</x-secondary-button>
                            </form>
                        </div>
                    </div>

                    @if ($contracts->isEmpty())
                        <div class="px-6 py-10 text-center">
                            <p class="text-sm font-medium text-slate-800">Nenhum contrato encontrado</p>
                            <p class="mt-1 text-sm text-slate-500">Cadastre um contrato ou ajuste os filtros.</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach ($contracts as $contract)
                                @php
                                    $nextPayable = $contract->payables->first(
                                        fn($payable) => $payable->paid_at === null,
                                    );
                                    $featuredPayable = $nextPayable ?? $contract->payables->last();
                                @endphp

                                <article class="px-5 py-5 sm:px-6">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="truncate font-semibold text-slate-900">
                                                {{ $contract->description }}</h3>
                                            <p class="mt-1 truncate text-sm text-slate-500">{{ $contract->provider }}
                                                · {{ $contract->unit->name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                R$ {{ number_format((float) $contract->amount, 2, ',', '.') }} por mês
                                                <span class="px-1 text-slate-300">·</span>
                                                vencimento dia {{ $contract->due_day }}
                                            </p>

                                            @if ($featuredPayable)
                                                @php($featuredStatus = $featuredPayable->status())
                                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                                    <span class="text-slate-600">
                                                        {{ $nextPayable ? 'Próximo vencimento' : 'Parcela paga mais recente' }}:
                                                        {{ $featuredPayable->due_date->format('d/m/Y') }}
                                                    </span>
                                                    <span @class([
                                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                                        'bg-emerald-50 text-emerald-700' => $featuredStatus === 'paid',
                                                        'bg-rose-50 text-rose-700' => $featuredStatus === 'overdue',
                                                        'bg-amber-50 text-amber-700' => $featuredStatus === 'pending',
                                                    ])>
                                                        {{ ['paid' => 'Paga', 'overdue' => 'Vencida', 'pending' => 'Pendente'][$featuredStatus] }}
                                                    </span>
                                                    <span class="font-semibold text-slate-800">R$
                                                        {{ number_format((float) $featuredPayable->amount, 2, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <p class="mt-3 text-sm text-slate-500">Nenhuma parcela encontrada para
                                                    este filtro.</p>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 flex-wrap items-center gap-3 sm:justify-end">
                                            <span
                                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                {{ $contract->payables->count() }}
                                                {{ \Illuminate\Support\Str::plural('parcela', $contract->payables->count()) }}
                                            </span>
                                            @if ($access['manage_contract'])
                                                @php($editingThis = (int) old('contract_id') === $contract->id)
                                                <div x-data x-init="@js($editingThis && $errors->any()) && $nextTick(() => $refs.editContract.showModal())">
                                                    <x-secondary-button type="button" class="whitespace-nowrap"
                                                        @click="$refs.editContract.showModal()">Editar contrato</x-secondary-button>
                                                    <dialog x-ref="editContract" aria-modal="true"
                                                        aria-labelledby="edit-contract-title-{{ $contract->id }}"
                                                        @click.self="$refs.editContract.close()"
                                                        class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-3xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
                                                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                                                            <h2 id="edit-contract-title-{{ $contract->id }}" class="font-semibold text-slate-900">Editar contrato</h2>
                                                            <p class="mt-1 text-sm leading-6 text-slate-500">As contas já pagas não são alteradas. As contas ainda não pagas serão ajustadas e as que faltarem serão geradas.</p>
                                                        </div>
                                                        <form method="POST" action="{{ route('payables.contracts.update', $contract) }}" class="space-y-4 p-5 sm:p-6">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                                                            <div>
                                                                <x-input-label for="edit-description-{{ $contract->id }}" value="Descrição do serviço" />
                                                                <x-text-input id="edit-description-{{ $contract->id }}" name="description" class="mt-1" required maxlength="255" :value="$editingThis ? old('description') : $contract->description" />
                                                                @if ($editingThis)<x-input-error :messages="$errors->get('description')" class="mt-2" />@endif
                                                            </div>
                                                            <div>
                                                                <x-input-label for="edit-supplier-{{ $contract->id }}" value="Fornecedor" />
                                                                <select id="edit-supplier-{{ $contract->id }}" name="supplier_id" required
                                                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                                                    @foreach ($availableSuppliers->when($contract->supplier && ! $availableSuppliers->contains('id', $contract->supplier_id), fn ($list) => $list->push($contract->supplier)) as $supplier)
                                                                        <option value="{{ $supplier->id }}" @selected(($editingThis ? old('supplier_id') : $contract->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @if ($editingThis)<x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />@endif
                                                            </div>
                                                            <div>
                                                                <x-input-label for="edit-unit-{{ $contract->id }}" value="Unidade / sede" />
                                                                <select id="edit-unit-{{ $contract->id }}" name="unit_id" required
                                                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                                                    @foreach ($availableUnits->when(! $availableUnits->contains('id', $contract->unit_id), fn ($list) => $list->push($contract->unit)) as $unit)
                                                                        <option value="{{ $unit->id }}" @selected(($editingThis ? old('unit_id') : $contract->unit_id) == $unit->id)>{{ $unit->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @if ($editingThis)<x-input-error :messages="$errors->get('unit_id')" class="mt-2" />@endif
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <x-input-label for="edit-amount-{{ $contract->id }}" value="Valor mensal (R$)" />
                                                                    <x-text-input id="edit-amount-{{ $contract->id }}" name="amount" type="number" step="0.01" min="0.01" class="mt-1" required :value="$editingThis ? old('amount') : number_format((float) $contract->amount, 2, '.', '')" />
                                                                    @if ($editingThis)<x-input-error :messages="$errors->get('amount')" class="mt-2" />@endif
                                                                </div>
                                                                <div>
                                                                    <x-input-label for="edit-due-day-{{ $contract->id }}" value="Dia do vencimento" />
                                                                    <x-text-input id="edit-due-day-{{ $contract->id }}" name="due_day" type="number" min="1" max="31" class="mt-1" required :value="$editingThis ? old('due_day') : $contract->due_day" />
                                                                    @if ($editingThis)<x-input-error :messages="$errors->get('due_day')" class="mt-2" />@endif
                                                                </div>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <x-input-label for="edit-starts-{{ $contract->id }}" value="Início do contrato" />
                                                                    <x-text-input id="edit-starts-{{ $contract->id }}" name="starts_on" type="date" class="mt-1" required :value="$editingThis ? old('starts_on') : $contract->starts_on->toDateString()" />
                                                                    @if ($editingThis)<x-input-error :messages="$errors->get('starts_on')" class="mt-2" />@endif
                                                                </div>
                                                                <div>
                                                                    <x-input-label for="edit-ends-{{ $contract->id }}" value="Término (opcional)" />
                                                                    <x-text-input id="edit-ends-{{ $contract->id }}" name="ends_on" type="date" class="mt-1" :value="$editingThis ? old('ends_on') : $contract->ends_on?->toDateString()" />
                                                                    @if ($editingThis)<x-input-error :messages="$errors->get('ends_on')" class="mt-2" />@endif
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                                <x-secondary-button type="button" @click="$refs.editContract.close()">Cancelar</x-secondary-button>
                                                                <x-primary-button class="justify-center">Salvar contrato</x-primary-button>
                                                            </div>
                                                        </form>
                                                    </dialog>
                                                </div>
                                                <form method="POST"
                                                    action="{{ route('payables.contracts.toggle', $contract) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-secondary-button type="submit" class="whitespace-nowrap">
                                                        {{ $contract->is_active ? 'Pausar contrato' : 'Retomar contrato' }}
                                                    </x-secondary-button>
                                                </form>
                                            @endif
                                            @if ($contract->payables->isNotEmpty())
                                                <div x-data x-init="@js((int) session('reopen_contract') === $contract->id) && $nextTick(() => $refs.installments.showModal())">
                                                    <button type="button" @click="$refs.installments.showModal()"
                                                        aria-label="Ver parcelas de {{ $contract->description }}"
                                                        class="inline-flex items-center gap-2 rounded-xl bg-brand-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" aria-hidden="true">
                                                            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"
                                                                stroke-width="2" stroke-linecap="round" />
                                                        </svg>
                                                        Ver parcelas
                                                    </button>

                                                    <dialog x-ref="installments" aria-modal="true"
                                                        aria-labelledby="installments-title-{{ $contract->id }}"
                                                        aria-describedby="installments-description-{{ $contract->id }}"
                                                        @click.self="$refs.installments.close()"
                                                        class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl overflow-hidden rounded-3xl border-0 bg-slate-50 p-0 shadow-2xl backdrop:bg-slate-950/60">
                                                        <div class="flex max-h-[90vh] flex-col bg-slate-50">
                                                            <header
                                                                class="bg-brand-gradient relative shrink-0 overflow-hidden px-5 py-5 text-white sm:px-7 sm:py-6">
                                                                <div class="absolute -right-8 -top-16 h-40 w-40 rounded-full bg-white/10 blur-2xl"
                                                                    aria-hidden="true"></div>
                                                                <div
                                                                    class="relative flex items-start justify-between gap-4">
                                                                    <div class="flex min-w-0 items-start gap-4">
                                                                        <span
                                                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 sm:h-12 sm:w-12">
                                                                            @if ($company->logo_path)
                                                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}"
                                                                                    alt="{{ $company->name }}"
                                                                                    class="h-8 w-8 object-contain sm:h-9 sm:w-9">
                                                                            @else
                                                                                <svg class="h-6 w-6"
                                                                                    viewBox="0 0 24 24" fill="none"
                                                                                    stroke="currentColor"
                                                                                    aria-hidden="true">
                                                                                    <rect x="4" y="3" width="16"
                                                                                        height="18" rx="2"
                                                                                        stroke-width="1.7" />
                                                                                    <path d="M8 8h8M8 12h8M8 16h4"
                                                                                        stroke-width="1.7"
                                                                                        stroke-linecap="round" />
                                                                                </svg>
                                                                            @endif
                                                                        </span>
                                                                        <div class="min-w-0">
                                                                            <p
                                                                                class="text-xs font-semibold uppercase tracking-[0.16em] text-white/85">
                                                                                Gestão do contrato</p>
                                                                            <h2 id="installments-title-{{ $contract->id }}"
                                                                                class="mt-1 truncate text-xl font-semibold tracking-tight sm:text-2xl">
                                                                                {{ $contract->description }}
                                                                            </h2>
                                                                            <p
                                                                                class="mt-1 truncate text-sm text-white/85">
                                                                                {{ $contract->provider }} ·
                                                                                {{ $contract->unit->name }}</p>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button"
                                                                        @click="$refs.installments.close()"
                                                                        aria-label="Fechar parcelas"
                                                                        class="shrink-0 rounded-xl border border-white/20 bg-white/10 p-2 text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white">
                                                                        <svg class="h-5 w-5" viewBox="0 0 20 20"
                                                                            fill="currentColor" aria-hidden="true">
                                                                            <path
                                                                                d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-3.72 3.72 3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 0 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                                                                        </svg>
                                                                    </button>
                                                                </div>

                                                                <div class="relative mt-5 flex flex-wrap gap-2">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">
                                                                        {{ $contract->payables->count() }}
                                                                        {{ \Illuminate\Support\Str::plural('parcela', $contract->payables->count()) }}
                                                                    </span>
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">
                                                                        Vencimento dia {{ $contract->due_day }}
                                                                    </span>
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">
                                                                        {{ $contract->is_active ? 'Contrato ativo' : 'Contrato pausado' }}
                                                                    </span>
                                                                </div>
                                                            </header>

                                                            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                                                                <div
                                                                    class="mb-4 flex flex-wrap items-end justify-between gap-2">
                                                                    <div>
                                                                        <h3 class="font-semibold text-slate-900">
                                                                            Parcelas</h3>
                                                                        <p id="installments-description-{{ $contract->id }}"
                                                                            class="mt-1 text-sm text-slate-500">
                                                                            Acompanhe vencimentos, valores e pagamentos.
                                                                        </p>
                                                                    </div>
                                                                    <span
                                                                        class="text-xs font-medium uppercase tracking-wide text-slate-400">Valores
                                                                        em R$</span>
                                                                </div>

                                                                <ul class="space-y-3">
                                                                    @foreach ($contract->payables as $payable)
                                                                        @php($status = $payable->status())
                                                                        <li
                                                                            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-brand-soft hover:shadow-md sm:p-5">
                                                                            <div
                                                                                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                                                <div class="flex items-center gap-3">
                                                                                    <span @class([
                                                                                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                                                                                        'bg-emerald-50 text-emerald-600' => $status === 'paid',
                                                                                        'bg-rose-50 text-rose-600' => $status === 'overdue',
                                                                                        'bg-amber-50 text-amber-600' => $status === 'pending',
                                                                                    ])>
                                                                                        <svg class="h-5 w-5"
                                                                                            viewBox="0 0 24 24"
                                                                                            fill="none"
                                                                                            stroke="currentColor"
                                                                                            aria-hidden="true">
                                                                                            <rect x="3.5" y="5"
                                                                                                width="17"
                                                                                                height="15.5"
                                                                                                rx="2"
                                                                                                stroke-width="1.7" />
                                                                                            <path
                                                                                                d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17"
                                                                                                stroke-width="1.7"
                                                                                                stroke-linecap="round" />
                                                                                        </svg>
                                                                                    </span>
                                                                                    <div>
                                                                                        <p
                                                                                            class="text-sm font-semibold text-slate-800">
                                                                                            {{ $payable->due_date->format('d/m/Y') }}
                                                                                        </p>
                                                                                        <p
                                                                                            class="mt-0.5 text-xs text-slate-500">
                                                                                            Vencimento da parcela</p>
                                                                                    </div>
                                                                                    <span @class([
                                                                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                                                                        'bg-emerald-50 text-emerald-700' => $status === 'paid',
                                                                                        'bg-rose-50 text-rose-700' => $status === 'overdue',
                                                                                        'bg-amber-50 text-amber-700' => $status === 'pending',
                                                                                    ])>
                                                                                        {{ ['paid' => 'Paga', 'overdue' => 'Vencida', 'pending' => 'Pendente'][$status] }}
                                                                                    </span>
                                                                                </div>

                                                                                <div
                                                                                    class="flex flex-wrap items-center gap-3 lg:justify-end">
                                                                                    @if ($payable->paid_at !== null)
                                                                                        <div
                                                                                            class="flex flex-col gap-1">
                                                                                            <span
                                                                                                class="text-base font-semibold text-slate-900">
                                                                                                R$
                                                                                                {{ number_format((float) $payable->amount, 2, ',', '.') }}
                                                                                            </span>
                                                                                            <span
                                                                                                class="text-xs text-slate-500">Valor
                                                                                                bloqueado após
                                                                                                pagamento</span>
                                                                                        </div>
                                                                                        @elseif ($access['update_amount'])
                                                                                        <form method="POST"
                                                                                            action="{{ route('payables.update', $payable) }}"
                                                                                            class="flex items-center gap-2"
                                                                                            x-data="{ originalAmount: '{{ number_format((float) $payable->amount, 2, '.', '') }}', isDirty: false }">
                                                                                            @csrf
                                                                                            @method('PATCH')
                                                                                            <label class="sr-only"
                                                                                                for="amount-{{ $payable->id }}">Valor
                                                                                                da parcela de
                                                                                                {{ $contract->description }}</label>
                                                                                            <span
                                                                                                class="text-xs font-medium text-slate-400">R$</span>
                                                                                            <input
                                                                                                id="amount-{{ $payable->id }}"
                                                                                                name="amount"
                                                                                                type="number"
                                                                                                step="0.01"
                                                                                                min="0.01"
                                                                                                value="{{ number_format((float) $payable->amount, 2, '.', '') }}"
                                                                                                @input="isDirty = $event.target.value === '' || Number($event.target.value) !== Number(originalAmount)"
                                                                                                class="w-28 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm font-semibold text-slate-800 focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                                                                            <button type="submit"
                                                                                                x-show="isDirty"
                                                                                                style="display: none"
                                                                                                class="rounded-lg bg-brand-muted px-3 py-2 text-xs font-semibold text-brand-primary transition hover:brightness-95">Salvar</button>
                                                                                        </form>
                                                                                    @else
                                                                                        <span
                                                                                            class="text-base font-semibold text-slate-900">
                                                                                            R$
                                                                                            {{ number_format((float) $payable->amount, 2, ',', '.') }}
                                                                                        </span>
                                                                                    @endif

                                                                                    @if ($access['change_payment_status'])
                                                                                        <form method="POST"
                                                                                            action="{{ route('payables.payment.toggle', $payable) }}"
                                                                                            x-data>
                                                                                            @csrf
                                                                                            @method('PATCH')
                                                                                            @if ($status === 'paid')
                                                                                                <button type="button"
                                                                                                    @click="$refs.confirmReopen.showModal()"
                                                                                                    aria-haspopup="dialog"
                                                                                                    aria-controls="confirm-reopen-{{ $payable->id }}"
                                                                                                    class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                                                                                    Desfazer pagamento
                                                                                                </button>
                                                                                                <dialog
                                                                                                    x-ref="confirmReopen"
                                                                                                    id="confirm-reopen-{{ $payable->id }}"
                                                                                                    aria-modal="true"
                                                                                                    aria-labelledby="confirm-reopen-title-{{ $payable->id }}"
                                                                                                    aria-describedby="confirm-reopen-description-{{ $payable->id }}"
                                                                                                    @click.self="$refs.confirmReopen.close()"
                                                                                                    class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border-0 bg-white p-0 text-left shadow-2xl backdrop:bg-slate-950/60">
                                                                                                    <div
                                                                                                        class="p-6 sm:p-7">
                                                                                                        <span
                                                                                                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                                                                                            <svg class="h-5 w-5"
                                                                                                                viewBox="0 0 24 24"
                                                                                                                fill="none"
                                                                                                                stroke="currentColor"
                                                                                                                aria-hidden="true">
                                                                                                                <path
                                                                                                                    d="M3 12a9 9 0 1 0 2.64-6.36L3 8m0-5v5h5m4-1v5l3 2"
                                                                                                                    stroke-width="1.8"
                                                                                                                    stroke-linecap="round"
                                                                                                                    stroke-linejoin="round" />
                                                                                                            </svg>
                                                                                                        </span>
                                                                                                        <h2 id="confirm-reopen-title-{{ $payable->id }}"
                                                                                                            class="mt-4 text-lg font-semibold tracking-tight text-slate-900">
                                                                                                            Confirmar
                                                                                                            reversão do
                                                                                                            pagamento?
                                                                                                        </h2>
                                                                                                        <p id="confirm-reopen-description-{{ $payable->id }}"
                                                                                                            class="mt-2 text-sm leading-6 text-slate-600">
                                                                                                            A parcela de
                                                                                                            {{ $payable->due_date->format('d/m/Y') }}
                                                                                                            voltará para
                                                                                                            pendente e
                                                                                                            poderá ser
                                                                                                            editada
                                                                                                            novamente.
                                                                                                        </p>
                                                                                                        <div
                                                                                                            class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                                                                            <button
                                                                                                                type="button"
                                                                                                                @click="$refs.confirmReopen.close()"
                                                                                                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                                                                                                                Manter
                                                                                                                como
                                                                                                                paga
                                                                                                            </button>
                                                                                                            <button
                                                                                                                type="submit"
                                                                                                                class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                                                                                                Sim,
                                                                                                                desfazer
                                                                                                                pagamento
                                                                                                            </button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </dialog>
                                                                                            @else
                                                                                                <button type="submit"
                                                                                                    class="rounded-xl bg-emerald-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                                                                                    Marcar paga
                                                                                                </button>
                                                                                            @endif
                                                                                        </form>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            <details
                                                                                class="mt-4 border-t border-slate-100 pt-3">
                                                                                <summary
                                                                                    class="cursor-pointer text-sm font-semibold text-brand-primary hover:brightness-90">
                                                                                    Histórico de alterações
                                                                                    ({{ $payable->histories->count() }})
                                                                                </summary>
                                                                                @if ($payable->histories->isEmpty())
                                                                                    <p
                                                                                        class="mt-3 text-sm text-slate-500">
                                                                                        Nenhuma alteração registrada
                                                                                        ainda.</p>
                                                                                @else
                                                                                    <ol class="mt-3 space-y-3">
                                                                                        @foreach ($payable->histories as $history)
                                                                                            <li
                                                                                                class="flex flex-col gap-1 rounded-xl bg-slate-50 px-3 py-2.5 sm:flex-row sm:items-start sm:justify-between">
                                                                                                <div>
                                                                                                    <p
                                                                                                        class="text-sm font-semibold text-slate-800">
                                                                                                        {{ $history->label() }}
                                                                                                    </p>
                                                                                                    @if ($history->action === \App\Models\PayableHistory::ACTION_AMOUNT_UPDATED)
                                                                                                        <p
                                                                                                            class="mt-1 text-sm text-slate-600">
                                                                                                            R$
                                                                                                            {{ number_format((float) $history->old_amount, 2, ',', '.') }}
                                                                                                            <span
                                                                                                                class="px-1 text-slate-400"
                                                                                                                aria-hidden="true">→</span>
                                                                                                            R$
                                                                                                            {{ number_format((float) $history->new_amount, 2, ',', '.') }}
                                                                                                        </p>
                                                                                                    @endif
                                                                                                    <p
                                                                                                        class="mt-1 text-xs text-slate-500">
                                                                                                        Por
                                                                                                        {{ $history->actor_name }}
                                                                                                    </p>
                                                                                                </div>
                                                                                                <time
                                                                                                    datetime="{{ $history->created_at->toIso8601String() }}"
                                                                                                    class="shrink-0 text-xs text-slate-500">
                                                                                                    {{ $history->created_at->format('d/m/Y H:i') }}
                                                                                                </time>
                                                                                            </li>
                                                                                        @endforeach
                                                                                    </ol>
                                                                                @endif
                                                                            </details>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </dialog>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                            {{ $contracts->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </section>
    </div>
</x-app-layout>
