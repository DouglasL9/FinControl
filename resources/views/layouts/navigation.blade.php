<nav x-data="{ open: false }" class="border-b border-slate-200/80 bg-white">
    @php($user = Auth::user())
    @php($companyMemberships = $user->companies()->orderBy('companies.name')->get())
    @php($companies = $companyMemberships->where('is_active', true))
    @php($archivedCompanies = $companyMemberships->filter(fn ($companyOption) => ! $companyOption->is_active && $companyOption->pivot->role === \App\Models\Company::ROLE_OWNER))
    @php($hasCompany = $currentCompany !== null)
    @php($membership = $currentCompany?->membershipFor($user))
    @php($permissions = $currentCompany?->permissionsFor($membership) ?? [])
    @php($canManagePermissions = $currentCompany?->isPermissionManager($membership) ?? false)
    @php($canViewDashboard = ! $hasCompany || ($permissions['dashboard.view'] ?? false))
    @php($canViewPayables = $hasCompany && ($permissions['payables.view'] ?? false))
    @php($canViewCompany = $hasCompany && (
        $canManagePermissions
        || ($permissions['units.view'] ?? false)
        || ($permissions['suppliers.view'] ?? false)
    ))

    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-10">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                        @if ($companyLogoUrl)
                            <img src="{{ $companyLogoUrl }}" alt="" class="h-10 w-10 rounded-xl border border-slate-200 bg-white p-1 object-contain">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary text-white shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path d="M4 19V5m0 14h16M8 15v-3m4 3V7m4 8v-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        @endif
                        <span class="max-w-40 truncate text-base font-semibold tracking-tight text-slate-900 sm:max-w-56">{{ $currentCompany?->name ?? 'FinControl' }}</span>
                    </a>
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    @if ($canViewDashboard)
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="rounded-lg px-3 py-2">
                            Visão geral
                        </x-nav-link>
                    @endif
                    @if ($hasCompany)
                        @if ($canViewPayables)
                            <x-nav-link :href="route('payables.index')" :active="request()->routeIs('payables.*')" class="rounded-lg px-3 py-2">
                                Contas a pagar
                            </x-nav-link>
                        @endif
                        @if ($canViewCompany)
                            <x-nav-link :href="route('company.show')" :active="request()->routeIs('company.*')" class="rounded-lg px-3 py-2">
                                Empresa
                            </x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <div class="hidden items-center sm:flex">
                <x-theme-toggle />
                @if ($companies->isNotEmpty() || $archivedCompanies->isNotEmpty())
                    <form method="POST" action="{{ route('companies.switch') }}" class="mr-4">
                        @csrf
                        <label class="sr-only" for="active_company_desktop">Empresa ativa</label>
                        <select id="active_company_desktop" name="company_id" @change="if ($event.target.value === 'create') { $event.target.value = '{{ $currentCompany?->getKey() }}'; $dispatch('open-modal', 'create-company'); } else { $event.target.form.submit(); }" class="max-w-48 rounded-lg border-slate-200 py-2 pl-3 pr-8 text-sm font-medium text-slate-700 focus:border-brand-primary focus:ring-brand-primary">
                            @foreach ($companies as $companyOption)
                                <option value="{{ $companyOption->id }}" @selected($currentCompany?->is($companyOption))>{{ $companyOption->name }}</option>
                            @endforeach
                            @if ($archivedCompanies->isNotEmpty())
                                <optgroup label="Empresas arquivadas">
                                    @foreach ($archivedCompanies as $companyOption)
                                        <option value="{{ $companyOption->id }}">Reativar: {{ $companyOption->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <option value="create">Criar empresa...</option>
                        </select>
                    </form>
                @else
                    <button type="button" @click="$dispatch('open-modal', 'create-company')" class="mr-4 rounded-lg px-3 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-muted focus:outline-none focus:ring-2 focus:ring-brand-primary">
                        Criar empresa
                    </button>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-xl px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="max-w-40 truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Meu perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Sair
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center gap-2 sm:hidden">
                <x-theme-toggle />
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-primary" :aria-expanded="open.toString()" :aria-label="open ? 'Fechar menu' : 'Abrir menu'">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        @if ($companies->isNotEmpty() || $archivedCompanies->isNotEmpty())
            <form method="POST" action="{{ route('companies.switch') }}" class="px-4 pb-3">
                @csrf
                <label class="mb-1 block text-xs font-medium text-slate-500" for="active_company_mobile">Empresa ativa</label>
                <select id="active_company_mobile" name="company_id" @change="if ($event.target.value === 'create') { $event.target.value = '{{ $currentCompany?->getKey() }}'; $dispatch('open-modal', 'create-company'); } else { $event.target.form.submit(); }" class="w-full rounded-lg border-slate-200 text-sm text-slate-700 focus:border-brand-primary focus:ring-brand-primary">
                    @foreach ($companies as $companyOption)
                        <option value="{{ $companyOption->id }}" @selected($currentCompany?->is($companyOption))>{{ $companyOption->name }}</option>
                    @endforeach
                    @if ($archivedCompanies->isNotEmpty())
                        <optgroup label="Empresas arquivadas">
                            @foreach ($archivedCompanies as $companyOption)
                                <option value="{{ $companyOption->id }}">Reativar: {{ $companyOption->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    <option value="create">Criar empresa...</option>
                </select>
            </form>
        @else
            <div class="px-4 pb-3">
                <button type="button" @click="$dispatch('open-modal', 'create-company')" class="block w-full rounded-lg px-3 py-2 text-start text-sm font-semibold text-brand-primary transition hover:bg-brand-muted focus:outline-none focus:ring-2 focus:ring-brand-primary">
                    Criar empresa
                </button>
            </div>
        @endif

        <div class="space-y-1 px-3 pb-3 pt-2">
            @if ($canViewDashboard)
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Visão geral
                </x-responsive-nav-link>
            @endif
            @if ($hasCompany)
                @if ($canViewPayables)
                    <x-responsive-nav-link :href="route('payables.index')" :active="request()->routeIs('payables.*')">
                        Contas a pagar
                    </x-responsive-nav-link>
                @endif
                @if ($canViewCompany)
                    <x-responsive-nav-link :href="route('company.show')" :active="request()->routeIs('company.*')">
                        Empresa
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <div class="border-t border-slate-200 px-4 pb-3 pt-4">
            <div>
                <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                <div class="mt-0.5 text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Meu perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Sair
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <x-modal name="create-company" focusable :show="session('openCompanyCreation', false)">
        <form method="POST" action="{{ route('companies.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold text-slate-900">Criar empresa</h2>
            <p class="mt-1 text-sm text-slate-500">Cadastre um novo espaço para organizar unidades, fornecedores e contas.</p>

            <div class="mt-5">
                <x-input-label for="nav_company_name" value="Nome da empresa" />
                <x-text-input id="nav_company_name" name="name" class="mt-1" :value="old('name')" required maxlength="255" />
                <x-input-error :messages="$errors->getBag('companyCreation')->get('name')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'create-company')">
                    Cancelar
                </x-secondary-button>
                <x-primary-button>Criar empresa</x-primary-button>
            </div>
        </form>
    </x-modal>
</nav>
