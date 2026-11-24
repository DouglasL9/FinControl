<x-app-layout>
    @php
        $query = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $statusOptions = $report === 'contracts'
            ? ['all' => 'Todos', 'active' => 'Ativos', 'paused' => 'Pausados']
            : ['all' => 'Todos', 'pending' => 'A vencer', 'overdue' => 'Vencidas', 'paid' => 'Pagas'];
        $toMoney = fn (string $value): float => (float) str_replace(',', '.', str_replace('.', '', preg_replace('/[^\d,.]/', '', $value)));
        $totalsByLabel = collect($totals)->pluck('value', 'label');
        $toneByLabel = [
            'Total' => 'indigo', 'Total pago' => 'emerald', 'Pago' => 'emerald', 'A pagar' => 'amber',
            'Pendente' => 'amber', 'Vencido' => 'rose', 'Ativos' => 'emerald', 'Pausados' => 'amber',
            'Mensal (ativos)' => 'indigo',
        ];
        $toneClasses = [
            'indigo' => ['bar' => 'bg-brand-primary', 'icon' => 'bg-brand-muted'],
            'emerald' => ['bar' => 'bg-emerald-500', 'icon' => 'bg-emerald-50'],
            'amber' => ['bar' => 'bg-amber-400', 'icon' => 'bg-amber-50'],
            'rose' => ['bar' => 'bg-rose-500', 'icon' => 'bg-rose-50'],
            'slate' => ['bar' => 'bg-slate-400', 'icon' => 'bg-slate-100'],
        ];
        $badgeClasses = [
            'Paga' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'Pendente' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'Vencida' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            'Ativo' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'Pausado' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        ];
        $isBadgeColumn = fn (string $column): bool => in_array($column, ['Status', 'Situação'], true);
        $isNumericColumn = fn (string $column): bool => in_array($column, ['Valor', 'Valor mensal', 'Dia', 'Parcelas', 'Total pago', 'A vencer', 'Vencido', 'Total'], true);
        $proportion = null;
        if ($report === 'payables' && $toMoney($totalsByLabel['Total'] ?? '0') > 0) {
            $grand = $toMoney($totalsByLabel['Total']);
            $proportion = [
                ['label' => 'Pago', 'bar' => 'bg-emerald-500', 'value' => $totalsByLabel['Pago'], 'pct' => $toMoney($totalsByLabel['Pago']) / $grand * 100],
                ['label' => 'Pendente', 'bar' => 'bg-amber-400', 'value' => $totalsByLabel['Pendente'], 'pct' => $toMoney($totalsByLabel['Pendente']) / $grand * 100],
                ['label' => 'Vencido', 'bar' => 'bg-rose-500', 'value' => $totalsByLabel['Vencido'], 'pct' => $toMoney($totalsByLabel['Vencido']) / $grand * 100],
            ];
        }
        $buttonBase = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2';
        $selectClasses = 'mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary';
    @endphp

    <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header
            class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"
                aria-hidden="true"></div>
            <div class="relative max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Gestão financeira</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Relatórios</h1>
                <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">
                    Consulte e exporte os dados de {{ $company->name }}.
                </p>
            </div>
        </header>

        <nav aria-label="Tipos de relatório" class="flex flex-wrap gap-2">
            @foreach ($types as $key => $label)
                <a href="{{ route('reports.show', $key) }}" @class([
                    'rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-primary',
                    'bg-brand-primary text-white shadow-sm' => $key === $report,
                    'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $key !== $report,
                ]) @if ($key === $report) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section aria-label="Filtros" class="rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Filtros</h2>
                <p class="mt-1 text-sm text-slate-500">Refine o período, a situação e os vínculos do relatório.</p>
            </div>
            <form method="GET" action="{{ route('reports.show', $report) }}" class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <x-input-label for="start" :value="$report === 'contracts' ? 'Vigência a partir de' : 'Vencimento de'" />
                        <x-text-input id="start" name="start" type="date" class="mt-1" :value="$filters['start'] ?? ''" />
                        <x-input-error :messages="$errors->get('start')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="end" :value="$report === 'contracts' ? 'Vigência até' : 'Vencimento até'" />
                        <x-text-input id="end" name="end" type="date" class="mt-1" :value="$filters['end'] ?? ''" />
                        <x-input-error :messages="$errors->get('end')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Situação" />
                        <select id="status" name="status" class="{{ $selectClasses }}">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="supplier_id" value="Fornecedor" />
                        <select id="supplier_id" name="supplier_id" class="{{ $selectClasses }}">
                            <option value="">Todos</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit_id" value="Unidade" />
                        <select id="unit_id" name="unit_id" class="{{ $selectClasses }}">
                            <option value="">Todas</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <a href="{{ route('reports.show', $report) }}"
                        class="{{ $buttonBase }} border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">Limpar</a>
                    <x-primary-button class="justify-center">Filtrar</x-primary-button>
                </div>
            </form>
        </section>

        <section aria-label="Totais" @class([
            'grid gap-4 sm:grid-cols-2',
            'xl:grid-cols-5' => count($totals) >= 5,
            'xl:grid-cols-4' => count($totals) === 4,
            'xl:grid-cols-3' => count($totals) <= 3,
        ])>
            @foreach ($totals as $total)
                @php $tone = $toneByLabel[$total['label']] ?? 'slate'; @endphp
                <article
                    class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="absolute inset-x-0 top-0 h-1 {{ $toneClasses[$tone]['bar'] }}"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $total['label'] }}</p>
                            <p class="mt-3 truncate text-2xl font-semibold tracking-tight text-slate-900">{{ $total['value'] }}</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $toneClasses[$tone]['icon'] }}"
                            aria-hidden="true">
                            <span class="h-2.5 w-2.5 rounded-full {{ $toneClasses[$tone]['bar'] }}"></span>
                        </span>
                    </div>
                </article>
            @endforeach
        </section>

        @if ($proportion)
            <section aria-label="Proporção por situação"
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-sm font-semibold text-slate-900">Distribuição do valor total</h2>
                <div class="mt-4 flex h-3 w-full overflow-hidden rounded-full bg-slate-100" role="img"
                    aria-label="Proporção entre valores pagos, pendentes e vencidos">
                    @foreach ($proportion as $part)
                        <div class="{{ $part['bar'] }}" style="width: {{ round($part['pct'], 2) }}%"></div>
                    @endforeach
                </div>
                <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($proportion as $part)
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full {{ $part['bar'] }}" aria-hidden="true"></span>
                            <div>
                                <dt class="text-xs text-slate-500">{{ $part['label'] }} · {{ number_format($part['pct'], 1, ',', '.') }}%</dt>
                                <dd class="text-sm font-semibold text-slate-800">{{ $part['value'] }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-slate-900">{{ $types[$report] }}</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $rowCount }} {{ $rowCount === 1 ? 'linha' : 'linhas' }}</span>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('reports.export', ['report' => $report] + $query) }}"
                        class="{{ $buttonBase }} border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Exportar CSV (Excel)</a>
                    <a href="{{ route('reports.print', ['report' => $report] + $query) }}" target="_blank" rel="noopener"
                        class="{{ $buttonBase }} bg-brand-primary text-white shadow-sm hover:brightness-95">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M7 9V4h10v5M7 17H5a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2M7 14h10v6H7v-6Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Imprimir / PDF</a>
                </div>
            </div>

            @if ($rowCount === 0)
                <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400" aria-hidden="true">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 12h6M9 16h6M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                    <p class="text-sm font-semibold text-slate-700">Nenhum registro encontrado para os filtros informados.</p>
                    <p class="text-sm text-slate-500">Ajuste o período ou a situação para ver resultados.</p>
                    <a href="{{ route('reports.show', $report) }}"
                        class="{{ $buttonBase }} border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">Limpar filtros</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                @foreach ($columns as $column)
                                    <th scope="col" @class(['whitespace-nowrap px-4 py-3', 'text-right' => $isNumericColumn($column), 'text-left' => ! $isNumericColumn($column)])>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($rows as $row)
                                <tr class="odd:bg-white even:bg-slate-50/50 transition hover:bg-brand-muted/40">
                                    @foreach ($row as $index => $cell)
                                        @php $column = $columns[$index] ?? ''; @endphp
                                        <td @class([
                                            'whitespace-nowrap px-4 py-3',
                                            'text-right tabular-nums' => $isNumericColumn($column),
                                            'font-medium text-slate-900' => $index === 0 || in_array($column, ['Total', 'Valor', 'Valor mensal'], true),
                                            'text-rose-700' => $column === 'Vencido' && $cell !== 'R$ 0,00',
                                        ])>
                                            @if ($isBadgeColumn($column) && isset($badgeClasses[$cell]))
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses[$cell] }}">{{ $cell }}</span>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($rowCount > $rowLimit)
                    <p class="border-t border-slate-100 bg-slate-50 px-6 py-3 text-xs text-slate-500">
                        Exibindo as primeiras {{ $rowLimit }} linhas. A exportação traz todas.</p>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
