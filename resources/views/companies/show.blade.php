@php
    $sectionLabels = [
        'company' => 'Empresa',
        'branding' => 'Identidade visual',
        'profiles' => 'Perfis de acesso',
        'team' => 'Equipe',
        'records' => 'Unidades e fornecedores',
        'archive' => 'Arquivar empresa',
    ];
    $companyDetailsErrors = $errors->getBag('companyDetails');
    $companyDetailsHasErrors = $companyDetailsErrors->isNotEmpty();
@endphp

<x-app-layout>
    <div
        class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
        x-data="{
            section: @js($activeSection),
            sections: @js($sections),
            anchors: { units: 'records', suppliers: 'records' },
            init() {
                const anchor = window.location.hash.slice(1);
                const target = this.anchors[anchor] ?? anchor;

                if (this.sections.includes(target)) {
                    this.section = target;
                    this.$nextTick(() => document.getElementById(anchor)?.scrollIntoView({ block: 'start' }));
                }
            },
            show(name) {
                this.section = name;
                const url = new URL(window.location.href);
                url.searchParams.set('section', name);
                url.hash = '';
                window.history.replaceState(null, '', url);
            },
        }"
    >
        <header class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Espaço da empresa</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $company->name }}</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">Dados cadastrais, identidade visual, acessos e cadastros compartilhados da empresa.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($access['units.view'])
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">{{ $company->units->count() }} unidades</span>
                        @endif
                        @if ($access['suppliers.view'])
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">{{ $company->suppliers->count() }} fornecedores</span>
                        @endif
                        @if ($canViewTeam)
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white">{{ $memberCount }} membros</span>
                        @endif
                    </div>
                </div>
                <span class="flex h-16 w-16 shrink-0 items-center justify-center self-start rounded-2xl border border-white/15 bg-white/10 p-2 sm:self-center">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo de {{ $company->name }}" class="h-full w-full object-contain">
                    @else
                        <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" stroke-width="2.2" stroke-linecap="round" />
                        </svg>
                    @endif
                </span>
            </div>
        </header>

        <nav aria-label="Seções das configurações" class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white p-1.5 shadow-sm">
            <div role="tablist" class="flex min-w-max gap-1">
                @foreach ($sections as $sectionKey)
                    <button
                        type="button"
                        role="tab"
                        id="settings-tab-{{ $sectionKey }}"
                        aria-controls="settings-panel-{{ $sectionKey }}"
                        aria-selected="{{ $sectionKey === $activeSection ? 'true' : 'false' }}"
                        :aria-selected="(section === '{{ $sectionKey }}').toString()"
                        @click="show('{{ $sectionKey }}')"
                        class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-primary"
                        :class="section === '{{ $sectionKey }}'
                            ? 'bg-brand-primary text-white shadow-sm'
                            : '{{ $sectionKey === 'archive' ? 'text-rose-700 hover:bg-rose-50 hover:text-rose-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}'"
                    >
                        {{ $sectionLabels[$sectionKey] }}
                    </button>
                @endforeach
            </div>
        </nav>

        <div
            id="settings-panel-company"
            role="tabpanel"
            aria-labelledby="settings-tab-company"
            x-show="section === 'company'"
            @if ($activeSection !== 'company') style="display: none;" @endif
        >
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5">
                    <h2 class="font-semibold text-slate-900">Configurações da empresa</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        @if ($isOwner)
                            Mantenha os dados cadastrais atualizados. O nome fantasia aparece no menu e no seletor de empresas da equipe.
                        @else
                            Dados cadastrais compartilhados com a equipe. Somente o proprietário pode alterá-los.
                        @endif
                    </p>
                </div>

                @if ($isOwner)
                    <form method="POST" action="{{ route('company.details.update') }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label for="company_name" value="Nome fantasia" />
                            <x-text-input id="company_name" name="name" class="mt-1" :value="$companyDetailsHasErrors ? old('name', $company->name) : $company->name" required maxlength="255" />
                            <x-input-error :messages="$companyDetailsErrors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="company_legal_name" value="Razão social" />
                            <x-text-input id="company_legal_name" name="legal_name" class="mt-1" :value="$companyDetailsHasErrors ? old('legal_name', $company->legal_name) : $company->legal_name" maxlength="255" />
                            <x-input-error :messages="$companyDetailsErrors->get('legal_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="company_cnpj" value="CNPJ" />
                            <x-text-input id="company_cnpj" name="cnpj" class="mt-1" :value="$companyDetailsHasErrors ? old('cnpj', $company->formattedCnpj()) : $company->formattedCnpj()" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" />
                            <x-input-error :messages="$companyDetailsErrors->get('cnpj')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="company_email" value="E-mail de contato" />
                            <x-text-input id="company_email" name="email" type="email" class="mt-1" :value="$companyDetailsHasErrors ? old('email', $company->email) : $company->email" maxlength="255" autocomplete="email" />
                            <x-input-error :messages="$companyDetailsErrors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="company_phone" value="Telefone" />
                            <x-text-input id="company_phone" name="phone" type="tel" class="mt-1" :value="$companyDetailsHasErrors ? old('phone', $company->phone) : $company->phone" maxlength="32" autocomplete="tel" />
                            <x-input-error :messages="$companyDetailsErrors->get('phone')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="company_address" value="Endereço" />
                            <textarea id="company_address" name="address" rows="2" maxlength="1000" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-primary focus:ring-brand-primary">{{ $companyDetailsHasErrors ? old('address', $company->address) : $company->address }}</textarea>
                            <x-input-error :messages="$companyDetailsErrors->get('address')" class="mt-2" />
                        </div>
                        <div class="flex justify-end sm:col-span-2">
                            <x-primary-button>Salvar dados da empresa</x-primary-button>
                        </div>
                    </form>
                @else
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            'Nome fantasia' => $company->name,
                            'Razão social' => $company->legal_name,
                            'CNPJ' => $company->formattedCnpj(),
                            'E-mail de contato' => $company->email,
                            'Telefone' => $company->phone,
                            'Endereço' => $company->address,
                        ] as $detailLabel => $detailValue)
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $detailLabel }}</dt>
                                <dd class="mt-1 break-words text-sm font-medium text-slate-800">{{ $detailValue ?: 'Não informado' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>
        </div>

        @if (in_array('branding', $sections, true))
        <div
            id="settings-panel-branding"
            role="tabpanel"
            aria-labelledby="settings-tab-branding"
            x-show="section === 'branding'"
            @if ($activeSection !== 'branding') style="display: none;" @endif
        >
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5">
                    <h2 class="font-semibold text-slate-900">Identidade visual</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Personalize as cores, a logo e a marca d’água compartilhadas com a equipe desta empresa.</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('company.branding.update') }}"
                    enctype="multipart/form-data"
                    class="grid gap-6 lg:grid-cols-2"
                    x-data="{ primaryColor: '{{ $company->primary_color }}', accentColor: '{{ $company->accent_color }}', watermarkOpacity: {{ $company->watermark_opacity }} }"
                >
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">
                        <div>
                            <x-input-label for="primary_color" value="Cor principal" />
                            <div class="mt-1 flex items-center gap-3">
                                <input id="primary_color" name="primary_color" type="color" value="{{ $company->primary_color }}" x-model="primaryColor" class="h-11 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                                <span class="text-sm font-medium text-slate-600" x-text="primaryColor"></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Prefira tons escuros para manter o contraste dos textos nos elementos coloridos.</p>
                            <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="accent_color" value="Cor de destaque" />
                            <div class="mt-1 flex items-center gap-3">
                                <input id="accent_color" name="accent_color" type="color" value="{{ $company->accent_color }}" x-model="accentColor" class="h-11 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                                <span class="text-sm font-medium text-slate-600" x-text="accentColor"></span>
                            </div>
                            <x-input-error :messages="$errors->get('accent_color')" class="mt-2" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <x-input-label for="watermark_opacity" value="Opacidade da marca d’água" />
                                <span class="text-sm font-semibold text-slate-700"><span x-text="watermarkOpacity"></span>%</span>
                            </div>
                            <input id="watermark_opacity" name="watermark_opacity" type="range" min="0" max="30" step="1" value="{{ $company->watermark_opacity }}" x-model.number="watermarkOpacity" class="mt-3 w-full accent-brand-primary">
                            <p class="mt-1 text-xs text-slate-500">A marca d’água usa a logo; 0% a oculta.</p>
                            <x-input-error :messages="$errors->get('watermark_opacity')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="company_logo" value="Logo da empresa" />
                            <input id="company_logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-brand-muted file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-primary">
                            <p class="mt-1 text-xs text-slate-500">PNG, JPG ou WebP; até 2 MB. SVG não é aceito.</p>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />

                            @if ($logoUrl)
                                <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo')) class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                                    Remover logo atual
                                </label>
                            @endif
                            <x-input-error :messages="$errors->get('remove_logo')" class="mt-2" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-primary-button>Salvar identidade visual</x-primary-button>
                            <button
                                type="button"
                                @click="primaryColor = '#4f46e5'; accentColor = '#7c3aed'; watermarkOpacity = 6"
                                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                Restaurar padrão
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-800">Prévia</p>
                        <div class="relative mt-2 flex min-h-52 flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="" class="pointer-events-none absolute inset-0 m-auto h-full w-full object-contain p-8" :style="{ opacity: watermarkOpacity / 100 }">
                            @else
                                <span class="pointer-events-none absolute inset-0 m-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-muted text-brand-primary" :style="{ opacity: watermarkOpacity / 100 }" aria-hidden="true">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M4 19V5m0 14h16M8 15v-3m4 3V7m4 8v-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            @endif
                            <div class="relative">
                                <p class="text-sm font-semibold text-slate-800">{{ $company->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">Prévia das cores no espaço da empresa</p>
                            </div>
                            <div class="relative flex flex-wrap items-center gap-3">
                                <span :style="{ backgroundColor: primaryColor }" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Ação principal</span>
                                <span :style="{ backgroundColor: accentColor }" class="rounded-full px-3 py-1.5 text-xs font-semibold text-white">Destaque</span>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
        </div>
        @endif

        @if (in_array('profiles', $sections, true))
        <div
            id="settings-panel-profiles"
            role="tabpanel"
            aria-labelledby="settings-tab-profiles"
            x-show="section === 'profiles'"
            @if ($activeSection !== 'profiles') style="display: none;" @endif
        >
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="font-semibold text-slate-900">Perfis de acesso</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        Crie perfis reutilizáveis com permissões por ação. O perfil “Administrador” mantém o acesso atual dos membros e não pode ser editado nem removido. O perfil “Padrão para convites” (inicialmente “Membro”, somente consulta) é atribuído a quem aceita um convite; só o proprietário o edita e ele não pode ser excluído. O grupo “Gestão da empresa” libera a equipe, os perfis e a identidade visual para quem não é proprietário, e ninguém concede permissões que não possui. Somente o proprietário designa administradores, convida pessoas, altera os dados cadastrais e arquiva a empresa. Associe cada perfil aos membros na seção Equipe.
                    </p>
                </div>

                @php($totalPermissions = count(\App\Models\CompanyPermissionProfile::permissionKeys()))
                @if ($company->permissionProfiles->isEmpty())
                    <div class="mt-5 flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-muted text-brand-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </span>
                        <p class="text-sm font-semibold text-slate-800">Nenhum perfil criado ainda</p>
                        <p class="max-w-sm text-sm text-slate-500">Use “Criar novo perfil” abaixo para definir o que cada pessoa da equipe pode fazer.</p>
                    </div>
                @endif

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($company->permissionProfiles as $profile)
                        @php($enabledCount = collect(\App\Models\CompanyPermissionProfile::normalize($profile->permissions))->filter()->count())
                        @php($profileFormIsOpen = old('profile_id') == $profile->id)
                        <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 p-4 transition hover:border-slate-300 sm:p-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-semibold text-slate-900">{{ $profile->name }}</h3>
                                    @if ($profile->is_invite_default)
                                        <span class="rounded-full bg-brand-muted px-2.5 py-0.5 text-xs font-medium text-brand-primary">Padrão para convites</span>
                                    @endif
                                    @if ($profile->is_default)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Protegido</span>
                                    @endif
                                </div>
                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span>{{ $profile->members_count }} {{ $profile->members_count === 1 ? 'usuário' : 'usuários' }}</span>
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $enabledCount }} de {{ $totalPermissions }} permissões</span>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($permissions as $group => $groupPermissions)
                                    @php($groupEnabled = collect(array_keys($groupPermissions))->filter(fn ($key) => $profile->permissions[$key] ?? false)->count())
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $groupEnabled > 0,
                                        'bg-slate-100 text-slate-400' => $groupEnabled === 0,
                                    ])>{{ $group }} · {{ $groupEnabled }}/{{ count($groupPermissions) }}</span>
                                @endforeach
                            </div>

                            @if (in_array($profile->id, $editableProfileIds, true))
                                @php($profileFormPermissions = $profileFormIsOpen ? old('permissions', []) : $profile->permissions)
                                <details class="group border-t border-slate-100 pt-3" @if ($profileFormIsOpen) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-brand-primary">
                                        <span>Editar perfil</span>
                                        <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </summary>
                                    <form method="POST" action="{{ route('company.permission-profiles.update', $profile) }}" class="mt-4 space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                                        <div>
                                            <x-input-label for="profile_name_{{ $profile->id }}" value="Nome do perfil" />
                                            <x-text-input id="profile_name_{{ $profile->id }}" name="name" class="mt-1" :value="$profileFormIsOpen ? old('name', $profile->name) : $profile->name" required maxlength="80" />
                                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                        </div>
                                        @foreach ($permissions as $group => $groupPermissions)
                                            <fieldset class="rounded-xl bg-slate-50 p-3">
                                                <legend class="px-1 text-sm font-semibold text-slate-800">{{ $group }}</legend>
                                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                    @foreach ($groupPermissions as $key => $label)
                                                        <label class="flex items-start gap-2 text-sm text-slate-600">
                                                            <input type="checkbox" name="permissions[{{ $key }}]" value="1" @checked($profileFormPermissions[$key] ?? false) @disabled(! $isOwner && ! ($access[$key] ?? false)) class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                                                            <span>{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </fieldset>
                                        @endforeach
                                        <x-input-error :messages="$errors->get('permissions')" />
                                        <div class="flex flex-wrap items-center justify-end gap-3">
                                            @if ($profile->members_count === 0 && ! $profile->is_invite_default)
                                                <button type="submit" form="delete_permission_profile_{{ $profile->id }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Excluir</button>
                                            @endif
                                            <x-primary-button>Salvar perfil</x-primary-button>
                                        </div>
                                    </form>
                                    @if ($profile->members_count === 0 && ! $profile->is_invite_default)
                                        <form id="delete_permission_profile_{{ $profile->id }}" method="POST" action="{{ route('company.permission-profiles.destroy', $profile) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>

                <details class="mt-5 rounded-2xl border border-dashed border-slate-300 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-brand-primary">Criar novo perfil</summary>
                    @php($newProfileIsOpen = old('profile_id') === 'new')
                    @php($newProfilePermissions = $newProfileIsOpen ? old('permissions', []) : [])
                    <form method="POST" action="{{ route('company.permission-profiles.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="profile_id" value="new">
                        <div class="max-w-md">
                            <x-input-label for="new_profile_name" value="Nome do perfil" />
                            <x-text-input id="new_profile_name" name="name" class="mt-1" :value="$newProfileIsOpen ? old('name') : ''" required maxlength="80" placeholder="Ex.: Consulta financeira" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        @foreach ($permissions as $group => $groupPermissions)
                            <fieldset class="rounded-xl bg-slate-50 p-3">
                                <legend class="px-1 text-sm font-semibold text-slate-800">{{ $group }}</legend>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($groupPermissions as $key => $label)
                                        <label class="flex items-start gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="permissions[{{ $key }}]" value="1" @checked($newProfilePermissions[$key] ?? false) @disabled(! $isOwner && ! ($access[$key] ?? false)) class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        <x-input-error :messages="$errors->get('permissions')" />
                        <div class="flex justify-end">
                            <x-primary-button>Criar perfil</x-primary-button>
                        </div>
                    </form>
                </details>
            </section>
        </div>
        @endif

        @if (in_array('team', $sections, true))
        <div
            id="settings-panel-team"
            role="tabpanel"
            aria-labelledby="settings-tab-team"
            x-show="section === 'team'"
            @if ($activeSection !== 'team') style="display: none;" @endif
            class="grid gap-6 xl:grid-cols-2"
        >
            @if ($isOwner)
                <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="font-semibold text-slate-900">Convidar alguém para a equipe</h2>
                    <p class="mt-1 text-sm text-slate-500">Busque alguém cadastrado ou informe um e-mail para enviar o convite. Ele expira em 7 dias.</p>

                    <form
                        x-data="companyUserPicker('{{ route('company-invitations.users.search') }}')"
                        method="POST"
                        action="{{ route('company-invitations.store') }}"
                        class="mt-5 flex flex-col gap-4"
                    >
                        @csrf
                        <div class="w-full max-w-md">
                            <x-input-label for="invite_user_search" value="Buscar usuário cadastrado" />
                            <x-text-input
                                id="invite_user_search"
                                type="search"
                                x-model="query"
                                @input.debounce.300ms="searchUsers"
                                class="mt-1"
                                placeholder="Nome ou e-mail (mínimo 2 caracteres)"
                                autocomplete="off"
                            />
                            <p x-show="loading" class="mt-2 text-sm text-slate-500" role="status">Buscando usuários...</p>
                            <p x-show="error" x-text="error" class="mt-2 text-sm text-rose-600" role="alert"></p>
                            <ul x-show="results.length > 0" class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <template x-for="user in results" :key="user.id">
                                    <li>
                                        <button
                                            type="button"
                                            @click="selectUser(user)"
                                            class="flex w-full flex-col px-3 py-2 text-left transition hover:bg-brand-muted focus:bg-brand-muted focus:outline-none"
                                        >
                                            <span class="text-sm font-medium text-slate-800" x-text="user.name"></span>
                                            <span class="text-xs text-slate-500" x-text="user.email"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p
                                x-show="query.trim().length >= 2 && !loading && !error && results.length === 0"
                                class="mt-2 text-sm text-slate-500"
                            >
                                Nenhum usuário disponível encontrado.
                            </p>
                            <p x-show="selectedNotice" x-text="selectedNotice" class="mt-2 text-sm text-emerald-700" role="status"></p>
                        </div>
                        <div class="w-full max-w-md">
                            <x-input-label for="invite_email" value="E-mail do membro" />
                            <x-text-input id="invite_email" name="email" type="email" x-model="email" class="mt-1" required autocomplete="email" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <x-primary-button class="w-fit">Enviar convite</x-primary-button>
                    </form>

                    @if ($company->invitations->isNotEmpty())
                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <h3 class="text-sm font-semibold text-slate-800">Convites pendentes</h3>
                            <ul class="mt-3 max-h-56 divide-y divide-slate-100 overflow-y-auto">
                                @foreach ($company->invitations as $invitation)
                                    <li class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                        <span class="font-medium text-slate-700">{{ $invitation->email }}</span>
                                        <span class="text-slate-500">Expira em {{ $invitation->expires_at->format('d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endif

            @if ($canViewTeam)
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                    <h2 class="font-semibold text-slate-900">Membros da empresa</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $company->users->count() }} pessoa(s) com acesso compartilhado.</p>
                </div>
                <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($company->users as $member)
                        @php($memberIsLocked = in_array($member->pivot->permission_profile_id, $hiddenProfileIds, true))
                        @php($canManageProfile = in_array($member->id, $assignableMemberIds, true))
                        @php($canManageRole = $isOwner && ! $memberIsLocked && $member->pivot->role !== \App\Models\Company::ROLE_OWNER)
                        @php($memberRoleLabel = [\App\Models\Company::ROLE_OWNER => 'Proprietário', \App\Models\Company::ROLE_ADMIN => 'Administrador'][$member->pivot->role] ?? 'Membro')
                        <li class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">
                                    {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $member->name }}</p>
                                    <p class="truncate text-sm text-slate-500">{{ $member->email }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $memberRoleLabel }}</span>
                                @if ($canManageProfile || $canManageRole)
                                    <button
                                        type="button"
                                        x-data="{}"
                                        x-on:click="$dispatch('open-modal', 'manage-member-{{ $member->id }}')"
                                        class="text-sm font-semibold text-brand-primary hover:brightness-90"
                                    >
                                        Gerenciar
                                    </button>
                                @endif
                            </div>

                            @if ($canManageProfile || $canManageRole)
                                <x-modal :name="'manage-member-'.$member->id" maxWidth="lg" focusable>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                                        <div class="min-w-0">
                                            <h2 class="text-lg font-semibold text-slate-900">Gerenciar acesso</h2>
                                            <p class="mt-1 truncate text-sm text-slate-500">{{ $member->name }} · {{ $member->email }}</p>
                                        </div>
                                        <button type="button" x-on:click="$dispatch('close')" class="text-slate-400 hover:text-slate-600" aria-label="Fechar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <div class="space-y-6 px-6 py-5">
                                        @if ($canManageProfile)
                                            <form method="POST" action="{{ route('company.members.permissions.update', $member) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="block text-sm font-medium text-slate-700" for="member_profile_{{ $member->id }}">Perfil de acesso</label>
                                                <div class="flex items-center gap-2">
                                                    <select id="member_profile_{{ $member->id }}" name="permission_profile_id" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 pl-3 pr-8 text-sm text-slate-700 focus:border-brand-primary focus:ring-brand-primary">
                                                        @foreach ($assignableProfiles as $profile)
                                                            <option value="{{ $profile->id }}" @selected($member->pivot->permission_profile_id == $profile->id)>{{ $profile->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-secondary-button type="submit">Salvar perfil</x-secondary-button>
                                                </div>
                                            </form>
                                        @endif
                                        @if ($canManageRole)
                                            <form method="POST" action="{{ route('company.members.role.update', $member) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="block text-sm font-medium text-slate-700" for="member_role_{{ $member->id }}">Papel na empresa</label>
                                                <div class="flex items-center gap-2">
                                                    <select id="member_role_{{ $member->id }}" name="role" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 pl-3 pr-8 text-sm text-slate-700 focus:border-brand-primary focus:ring-brand-primary">
                                                        <option value="{{ \App\Models\Company::ROLE_MEMBER }}" @selected($member->pivot->role === \App\Models\Company::ROLE_MEMBER)>Membro</option>
                                                        <option value="{{ \App\Models\Company::ROLE_ADMIN }}" @selected($member->pivot->role === \App\Models\Company::ROLE_ADMIN)>Administrador</option>
                                                    </select>
                                                    <x-secondary-button type="submit">Atualizar papel</x-secondary-button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </x-modal>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>
        @endif

        @if (in_array('records', $sections, true))
        <div
            id="settings-panel-records"
            role="tabpanel"
            aria-labelledby="settings-tab-records"
            x-show="section === 'records'"
            @if ($activeSection !== 'records') style="display: none;" @endif
            class="space-y-6"
        >
            @if ($access['units.view'])
            <section id="units" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="font-semibold text-slate-900">Unidades e sedes</h2>
                        <p class="mt-1 text-sm text-slate-500">Mantenha endereço e contato atualizados. Unidades arquivadas continuam no histórico, mas não podem receber novos contratos.</p>
                    </div>
                    @if ($access['units.create'])
                        <x-primary-button type="button" x-data="{}" x-on:click="$dispatch('open-modal', 'create-unit')">
                            Adicionar unidade
                        </x-primary-button>
                    @endif
                </div>
                @if ($company->units->isEmpty())
                    <p class="px-5 py-4 text-sm text-slate-500 sm:px-6">Nenhuma unidade cadastrada.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($company->units as $unit)
                            <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-800">{{ $unit->name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        {{ collect([
                                            $unit->street,
                                            $unit->number,
                                            $unit->complement,
                                            $unit->neighborhood,
                                            $unit->city,
                                            $unit->state,
                                            $unit->postal_code ? 'CEP '.$unit->postal_code : null,
                                            $unit->phone,
                                        ])->filter()->implode(' · ') ?: 'Endereço e contato não informados' }}
                                    </span>
                                </span>
                                <span class="flex flex-wrap items-center gap-3">
                                    @if ($unit->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Arquivada</span>
                                    @endif
                                    @if ($access['units.update'])
                                        <button
                                            type="button"
                                            x-data="{}"
                                            x-on:click="$dispatch('open-modal', 'edit-unit-{{ $unit->id }}')"
                                            class="text-sm font-semibold text-brand-primary hover:brightness-90"
                                        >
                                            Editar
                                        </button>
                                    @endif
                                    @if ($access['units.archive'])
                                        <form method="POST" action="{{ route('company.units.toggle', $unit) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-semibold {{ $unit->is_active ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800' }}">
                                                {{ $unit->is_active ? 'Arquivar' : 'Reativar' }}
                                            </button>
                                        </form>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            @endif

            @if ($access['suppliers.view'])
            <section id="suppliers" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="font-semibold text-slate-900">Fornecedores</h2>
                        <p class="mt-1 text-sm text-slate-500">Cadastre os dados uma vez e selecione o fornecedor ao criar cada contrato. Arquivar preserva os contratos e o histórico.</p>
                    </div>
                    @if ($access['suppliers.create'])
                        <x-primary-button type="button" x-data="{}" x-on:click="$dispatch('open-modal', 'create-supplier')">
                            Cadastrar fornecedor
                        </x-primary-button>
                    @endif
                </div>
                @if ($company->suppliers->isEmpty())
                    <p class="px-5 py-4 text-sm text-slate-500 sm:px-6">Nenhum fornecedor cadastrado.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($company->suppliers as $supplier)
                            <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-800">{{ $supplier->name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">
                                        {{ collect([
                                            $supplier->formattedCnpj(),
                                            $supplier->contact_name,
                                            $supplier->email,
                                            $supplier->phone,
                                            $supplier->address,
                                        ])->filter()->implode(' · ') ?: 'Sem CNPJ ou contato informado' }}
                                    </span>
                                </span>
                                <span class="flex flex-wrap items-center gap-3">
                                    @if ($supplier->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Arquivado</span>
                                    @endif
                                    @if ($access['suppliers.update'])
                                        <button
                                            type="button"
                                            x-data="{}"
                                            x-on:click="$dispatch('open-modal', 'edit-supplier-{{ $supplier->id }}')"
                                            class="text-sm font-semibold text-brand-primary hover:brightness-90"
                                        >
                                            Editar
                                        </button>
                                    @endif
                                    @if ($access['suppliers.archive'])
                                        <form method="POST" action="{{ route('company.suppliers.toggle', $supplier) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-semibold {{ $supplier->is_active ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800' }}">
                                                {{ $supplier->is_active ? 'Arquivar' : 'Reativar' }}
                                            </button>
                                        </form>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            @endif
        </div>
        @endif

        @if (in_array('archive', $sections, true))
        <div
            id="settings-panel-archive"
            role="tabpanel"
            aria-labelledby="settings-tab-archive"
            x-show="section === 'archive'"
            @if ($activeSection !== 'archive') style="display: none;" @endif
        >
            <section class="rounded-3xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm sm:p-6">
                <h2 class="font-semibold text-rose-900">Arquivar empresa</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-rose-800">
                    A equipe perderá o acesso e a empresa sairá da lista de empresas ativas, do painel e do seletor. Nenhum dado será apagado: contratos, parcelas, históricos e convites ficam guardados, e você poderá reativá-la pelo seletor de empresas.
                </p>

                <div x-data x-init="@js($errors->has('password')) && $nextTick(() => $refs.archiveCompany.showModal())" class="mt-4">
                    <x-danger-button type="button" @click="$refs.archiveCompany.showModal()">Arquivar empresa</x-danger-button>

                    <dialog x-ref="archiveCompany" aria-modal="true" aria-labelledby="archive-company-title"
                        @click.self="$refs.archiveCompany.close()"
                        class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-3xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="archive-company-title" class="font-semibold text-slate-900">Arquivar {{ $company->name }}?</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Todos os membros perdem o acesso imediatamente e a empresa deixa de gerar parcelas até ser reativada.</p>
                            </div>
                            <button type="button" @click="$refs.archiveCompany.close()" aria-label="Fechar"
                                class="shrink-0 rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-3.72 3.72 3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 0 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('companies.archive', $company) }}" class="space-y-4 p-5 sm:p-6">
                            @csrf
                            <div>
                                <x-input-label for="archive_company_password" value="Confirme sua senha atual" />
                                <x-text-input id="archive_company_password" name="password" type="password" class="mt-1" required autocomplete="current-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <x-secondary-button type="button" @click="$refs.archiveCompany.close()">Cancelar</x-secondary-button>
                                <x-danger-button>Sim, arquivar empresa</x-danger-button>
                            </div>
                        </form>
                    </dialog>
                </div>
            </section>
        </div>
        @endif

        @if ($access['units.create'])
        @php($createUnitIsOpen = old('_modal') === 'create-unit')
        @php($createUnitErrors = $createUnitIsOpen ? $errors->getBag('default') : new \Illuminate\Support\MessageBag())
        <x-modal name="create-unit" :show="$createUnitIsOpen" maxWidth="2xl" focusable>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Adicionar unidade</h2>
                    <p class="mt-1 text-sm text-slate-500">Informe os dados da sede ou filial.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close-modal', 'create-unit')" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('company.units.store') }}" class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3" x-data="unitAddressLookup">
                @csrf
                <input type="hidden" name="_modal" value="create-unit">
                <div>
                    <x-input-label for="unit_name" value="Nome da unidade" />
                    <x-text-input id="unit_name" name="unit_name" class="mt-1" :value="$createUnitIsOpen ? old('unit_name') : ''" required maxlength="255" placeholder="Ex.: Filial Campinas" />
                    <x-input-error :messages="$createUnitErrors->get('unit_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="unit_postal_code" value="CEP" />
                    <x-text-input id="unit_postal_code" name="postal_code" x-ref="postalCode" x-on:input.debounce.500ms="lookupCep" class="mt-1" :value="$createUnitIsOpen ? old('postal_code') : ''" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code" />
                    <x-input-error :messages="$createUnitErrors->get('postal_code')" class="mt-2" />
                    <p x-cloak x-show="lookupMessage" x-text="lookupMessage" x-bind:class="{ 'text-slate-500': lookupState === 'loading', 'text-emerald-700': lookupState === 'success', 'text-rose-600': lookupState === 'error' }" class="mt-1 text-xs" role="status" aria-live="polite"></p>
                </div>
                <div>
                    <x-input-label for="unit_street" value="Rua / avenida" />
                    <x-text-input id="unit_street" name="street" x-ref="street" class="mt-1" :value="$createUnitIsOpen ? old('street') : ''" maxlength="255" />
                    <x-input-error :messages="$createUnitErrors->get('street')" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="unit_number" value="Número" />
                        <x-text-input id="unit_number" name="number" class="mt-1" :value="$createUnitIsOpen ? old('number') : ''" maxlength="32" />
                        <x-input-error :messages="$createUnitErrors->get('number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit_complement" value="Complemento" />
                        <x-text-input id="unit_complement" name="complement" class="mt-1" :value="$createUnitIsOpen ? old('complement') : ''" maxlength="255" />
                        <x-input-error :messages="$createUnitErrors->get('complement')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="unit_neighborhood" value="Bairro" />
                    <x-text-input id="unit_neighborhood" name="neighborhood" x-ref="neighborhood" class="mt-1" :value="$createUnitIsOpen ? old('neighborhood') : ''" maxlength="255" />
                    <x-input-error :messages="$createUnitErrors->get('neighborhood')" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="unit_city" value="Cidade" />
                        <x-text-input id="unit_city" name="city" x-ref="city" class="mt-1" :value="$createUnitIsOpen ? old('city') : ''" maxlength="255" />
                        <x-input-error :messages="$createUnitErrors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit_state" value="UF" />
                        <x-text-input id="unit_state" name="state" x-ref="state" class="mt-1 uppercase" :value="$createUnitIsOpen ? old('state') : ''" maxlength="2" placeholder="SP" />
                        <x-input-error :messages="$createUnitErrors->get('state')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="unit_phone" value="Telefone" />
                    <x-text-input id="unit_phone" name="phone" type="tel" class="mt-1" :value="$createUnitIsOpen ? old('phone') : ''" maxlength="32" />
                    <x-input-error :messages="$createUnitErrors->get('phone')" class="mt-2" />
                </div>
                <div class="flex flex-wrap justify-end gap-3 sm:col-span-2 xl:col-span-3">
                    <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'create-unit')">Cancelar</x-secondary-button>
                    <x-primary-button>Adicionar unidade</x-primary-button>
                </div>
            </form>
        </x-modal>
        @endif

        @if ($access['units.update'])
        @foreach ($company->units as $unit)
            @php($editUnitModal = 'edit-unit-'.$unit->id)
            @php($editUnitIsOpen = old('_modal') === $editUnitModal)
            @php($editUnitErrors = $editUnitIsOpen ? $errors->getBag('default') : new \Illuminate\Support\MessageBag())
            <x-modal :name="$editUnitModal" :show="$editUnitIsOpen" maxWidth="2xl" focusable>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Editar unidade</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $unit->name }}</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', '{{ $editUnitModal }}')" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('company.units.update', $unit) }}" class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3" x-data="unitAddressLookup">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_modal" value="{{ $editUnitModal }}">
                    <div>
                        <x-input-label for="edit_unit_name_{{ $unit->id }}" value="Nome da unidade" />
                        <x-text-input id="edit_unit_name_{{ $unit->id }}" name="unit_name" class="mt-1" :value="$editUnitIsOpen ? old('unit_name', $unit->name) : $unit->name" required maxlength="255" />
                        <x-input-error :messages="$editUnitErrors->get('unit_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_unit_postal_code_{{ $unit->id }}" value="CEP" />
                        <x-text-input id="edit_unit_postal_code_{{ $unit->id }}" name="postal_code" x-ref="postalCode" x-on:input.debounce.500ms="lookupCep" class="mt-1" :value="$editUnitIsOpen ? old('postal_code', $unit->postal_code) : $unit->postal_code" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code" />
                        <x-input-error :messages="$editUnitErrors->get('postal_code')" class="mt-2" />
                        <p x-cloak x-show="lookupMessage" x-text="lookupMessage" x-bind:class="{ 'text-slate-500': lookupState === 'loading', 'text-emerald-700': lookupState === 'success', 'text-rose-600': lookupState === 'error' }" class="mt-1 text-xs" role="status" aria-live="polite"></p>
                    </div>
                    <div>
                        <x-input-label for="edit_unit_street_{{ $unit->id }}" value="Rua / avenida" />
                        <x-text-input id="edit_unit_street_{{ $unit->id }}" name="street" x-ref="street" class="mt-1" :value="$editUnitIsOpen ? old('street', $unit->street) : $unit->street" maxlength="255" />
                        <x-input-error :messages="$editUnitErrors->get('street')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="edit_unit_number_{{ $unit->id }}" value="Número" />
                            <x-text-input id="edit_unit_number_{{ $unit->id }}" name="number" class="mt-1" :value="$editUnitIsOpen ? old('number', $unit->number) : $unit->number" maxlength="32" />
                            <x-input-error :messages="$editUnitErrors->get('number')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="edit_unit_complement_{{ $unit->id }}" value="Complemento" />
                            <x-text-input id="edit_unit_complement_{{ $unit->id }}" name="complement" class="mt-1" :value="$editUnitIsOpen ? old('complement', $unit->complement) : $unit->complement" maxlength="255" />
                            <x-input-error :messages="$editUnitErrors->get('complement')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="edit_unit_neighborhood_{{ $unit->id }}" value="Bairro" />
                        <x-text-input id="edit_unit_neighborhood_{{ $unit->id }}" name="neighborhood" x-ref="neighborhood" class="mt-1" :value="$editUnitIsOpen ? old('neighborhood', $unit->neighborhood) : $unit->neighborhood" maxlength="255" />
                        <x-input-error :messages="$editUnitErrors->get('neighborhood')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="edit_unit_city_{{ $unit->id }}" value="Cidade" />
                            <x-text-input id="edit_unit_city_{{ $unit->id }}" name="city" x-ref="city" class="mt-1" :value="$editUnitIsOpen ? old('city', $unit->city) : $unit->city" maxlength="255" />
                            <x-input-error :messages="$editUnitErrors->get('city')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="edit_unit_state_{{ $unit->id }}" value="UF" />
                            <x-text-input id="edit_unit_state_{{ $unit->id }}" name="state" x-ref="state" class="mt-1 uppercase" :value="$editUnitIsOpen ? old('state', $unit->state) : $unit->state" maxlength="2" placeholder="SP" />
                            <x-input-error :messages="$editUnitErrors->get('state')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="edit_unit_phone_{{ $unit->id }}" value="Telefone" />
                        <x-text-input id="edit_unit_phone_{{ $unit->id }}" name="phone" type="tel" class="mt-1" :value="$editUnitIsOpen ? old('phone', $unit->phone) : $unit->phone" maxlength="32" />
                        <x-input-error :messages="$editUnitErrors->get('phone')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap justify-end gap-3 sm:col-span-2 xl:col-span-3">
                        <x-secondary-button type="button" x-on:click="$dispatch('close-modal', '{{ $editUnitModal }}')">Cancelar</x-secondary-button>
                        <x-primary-button>Salvar alterações</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
        @endif

        @if ($access['suppliers.create'])
        @php($createSupplierIsOpen = old('_modal') === 'create-supplier')
        @php($createSupplierErrors = $createSupplierIsOpen ? $errors->getBag('default') : new \Illuminate\Support\MessageBag())
        <x-modal name="create-supplier" :show="$createSupplierIsOpen" maxWidth="2xl" focusable>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Cadastrar fornecedor</h2>
                    <p class="mt-1 text-sm text-slate-500">Informe os dados para reutilizar o fornecedor nos contratos da empresa.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close-modal', 'create-supplier')" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('company.suppliers.store') }}" class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <input type="hidden" name="_modal" value="create-supplier">
                <div>
                    <x-input-label for="supplier_name" value="Nome do fornecedor" />
                    <x-text-input id="supplier_name" name="name" class="mt-1" :value="$createSupplierIsOpen ? old('name') : ''" required maxlength="255" placeholder="Ex.: Provedor de internet" />
                    <x-input-error :messages="$createSupplierErrors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="supplier_cnpj" value="CNPJ (opcional)" />
                    <x-text-input id="supplier_cnpj" name="cnpj" class="mt-1" :value="$createSupplierIsOpen ? old('cnpj') : ''" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" />
                    <x-input-error :messages="$createSupplierErrors->get('cnpj')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="supplier_contact_name" value="Pessoa de contato" />
                    <x-text-input id="supplier_contact_name" name="contact_name" class="mt-1" :value="$createSupplierIsOpen ? old('contact_name') : ''" maxlength="255" />
                    <x-input-error :messages="$createSupplierErrors->get('contact_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="supplier_email" value="E-mail" />
                    <x-text-input id="supplier_email" name="email" type="email" class="mt-1" :value="$createSupplierIsOpen ? old('email') : ''" maxlength="255" />
                    <x-input-error :messages="$createSupplierErrors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="supplier_phone" value="Telefone" />
                    <x-text-input id="supplier_phone" name="phone" type="tel" class="mt-1" :value="$createSupplierIsOpen ? old('phone') : ''" maxlength="32" />
                    <x-input-error :messages="$createSupplierErrors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="supplier_address" value="Endereço" />
                    <x-text-input id="supplier_address" name="address" class="mt-1" :value="$createSupplierIsOpen ? old('address') : ''" maxlength="1000" />
                    <x-input-error :messages="$createSupplierErrors->get('address')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-input-label for="supplier_notes" value="Observações" />
                    <textarea id="supplier_notes" name="notes" rows="2" maxlength="1000" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-primary focus:ring-brand-primary">{{ $createSupplierIsOpen ? old('notes') : '' }}</textarea>
                    <x-input-error :messages="$createSupplierErrors->get('notes')" class="mt-2" />
                </div>
                <div class="flex flex-wrap justify-end gap-3 sm:col-span-2 lg:col-span-3">
                    <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'create-supplier')">Cancelar</x-secondary-button>
                    <x-primary-button>Cadastrar fornecedor</x-primary-button>
                </div>
            </form>
        </x-modal>
        @endif

        @if ($access['suppliers.update'])
        @foreach ($company->suppliers as $supplier)
            @php($editSupplierModal = 'edit-supplier-'.$supplier->id)
            @php($editSupplierIsOpen = old('_modal') === $editSupplierModal)
            @php($editSupplierErrors = $editSupplierIsOpen ? $errors->getBag('default') : new \Illuminate\Support\MessageBag())
            <x-modal :name="$editSupplierModal" :show="$editSupplierIsOpen" maxWidth="2xl" focusable>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Editar fornecedor</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $supplier->name }}</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', '{{ $editSupplierModal }}')" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('company.suppliers.update', $supplier) }}" class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_modal" value="{{ $editSupplierModal }}">
                    <div>
                        <x-input-label for="edit_supplier_name_{{ $supplier->id }}" value="Nome do fornecedor" />
                        <x-text-input id="edit_supplier_name_{{ $supplier->id }}" name="name" class="mt-1" :value="$editSupplierIsOpen ? old('name', $supplier->name) : $supplier->name" required maxlength="255" />
                        <x-input-error :messages="$editSupplierErrors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_supplier_cnpj_{{ $supplier->id }}" value="CNPJ" />
                        <x-text-input id="edit_supplier_cnpj_{{ $supplier->id }}" name="cnpj" class="mt-1" :value="$editSupplierIsOpen ? old('cnpj', $supplier->cnpj) : $supplier->cnpj" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" />
                        <x-input-error :messages="$editSupplierErrors->get('cnpj')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_supplier_contact_name_{{ $supplier->id }}" value="Pessoa de contato" />
                        <x-text-input id="edit_supplier_contact_name_{{ $supplier->id }}" name="contact_name" class="mt-1" :value="$editSupplierIsOpen ? old('contact_name', $supplier->contact_name) : $supplier->contact_name" maxlength="255" />
                        <x-input-error :messages="$editSupplierErrors->get('contact_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_supplier_email_{{ $supplier->id }}" value="E-mail" />
                        <x-text-input id="edit_supplier_email_{{ $supplier->id }}" name="email" type="email" class="mt-1" :value="$editSupplierIsOpen ? old('email', $supplier->email) : $supplier->email" maxlength="255" />
                        <x-input-error :messages="$editSupplierErrors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_supplier_phone_{{ $supplier->id }}" value="Telefone" />
                        <x-text-input id="edit_supplier_phone_{{ $supplier->id }}" name="phone" type="tel" class="mt-1" :value="$editSupplierIsOpen ? old('phone', $supplier->phone) : $supplier->phone" maxlength="32" />
                        <x-input-error :messages="$editSupplierErrors->get('phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_supplier_address_{{ $supplier->id }}" value="Endereço" />
                        <x-text-input id="edit_supplier_address_{{ $supplier->id }}" name="address" class="mt-1" :value="$editSupplierIsOpen ? old('address', $supplier->address) : $supplier->address" maxlength="1000" />
                        <x-input-error :messages="$editSupplierErrors->get('address')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-input-label for="edit_supplier_notes_{{ $supplier->id }}" value="Observações" />
                        <textarea id="edit_supplier_notes_{{ $supplier->id }}" name="notes" rows="2" maxlength="1000" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-primary focus:ring-brand-primary">{{ $editSupplierIsOpen ? old('notes', $supplier->notes) : $supplier->notes }}</textarea>
                        <x-input-error :messages="$editSupplierErrors->get('notes')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap justify-end gap-3 sm:col-span-2 lg:col-span-3">
                        <x-secondary-button type="button" x-on:click="$dispatch('close-modal', '{{ $editSupplierModal }}')">Cancelar</x-secondary-button>
                        <x-primary-button>Salvar alterações</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
        @endif
    </div>
</x-app-layout>
