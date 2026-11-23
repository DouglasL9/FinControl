<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Minhas empresas</h2>
        <p class="mt-1 text-sm leading-6 text-slate-500">Empresas das quais você participa e o seu nível de acesso em cada uma.</p>
    </header>

    @if ($memberships->isEmpty())
        <p class="mt-6 rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">Você ainda não participa de nenhuma empresa.</p>
    @else
        <ul class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200/80">
            @foreach ($memberships as $membership)
                <li class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">{{ $membership['company']->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $membership['roleLabel'] }}@if ($membership['profileName']) · Perfil: {{ $membership['profileName'] }}@endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        @if ($membership['isCurrent'])
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Empresa ativa</span>
                        @endif
                        @unless ($membership['company']->is_active)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Arquivada</span>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
