@php
    $status = session('status');
    $message = is_string($status) ? match ($status) {
        'profile-updated' => 'Perfil atualizado com sucesso.',
        'password-updated' => 'Senha atualizada com sucesso.',
        'verification-link-sent' => 'Link de verificação enviado para o seu e-mail.',
        'account-deleted' => 'Conta excluída com sucesso.',
        'logged-in' => 'Login realizado com sucesso.',
        'logged-out' => 'Você saiu da sua conta.',
        'email-verified' => 'E-mail verificado com sucesso.',
        'company-created' => 'Empresa criada com sucesso.',
        'company-switched' => 'Empresa ativa alterada.',
        'company-archived' => 'Empresa arquivada. Os dados foram mantidos e o acesso da equipe foi suspenso.',
        'company-restored' => 'Empresa reativada com sucesso.',
        'company-details-updated' => 'Dados cadastrais da empresa atualizados.',
        'company-branding-updated' => 'Identidade visual da empresa atualizada.',
        'unit-created' => 'Unidade cadastrada com sucesso.',
        'unit-updated' => 'Dados da unidade atualizados.',
        'unit-archived' => 'Unidade arquivada; o histórico foi preservado.',
        'unit-activated' => 'Unidade reativada.',
        'supplier-created' => 'Fornecedor cadastrado com sucesso.',
        'supplier-updated' => 'Dados do fornecedor atualizados.',
        'supplier-archived' => 'Fornecedor arquivado; os contratos foram preservados.',
        'supplier-activated' => 'Fornecedor reativado.',
        'company-joined' => 'Você entrou na empresa.',
        'invitation-sent' => 'Convite enviado por e-mail.',
        'permission-profile-created' => 'Perfil de acesso criado.',
        'permission-profile-updated' => 'Perfil de acesso atualizado.',
        'permission-profile-deleted' => 'Perfil de acesso excluído.',
        'member-permissions-updated' => 'Perfil de acesso do usuário atualizado.',
        'member-role-updated' => 'Papel do usuário atualizado.',
        'contract-created' => 'Contrato recorrente cadastrado.',
        'contract-updated' => 'Contrato atualizado; as contas não pagas foram ajustadas.',
        'contract-paused' => 'Geração de novas contas pausada.',
        'contract-resumed' => 'Geração de novas contas retomada.',
        'payable-updated' => 'Valor da conta atualizado.',
        'payable-paid' => 'Conta marcada como paga.',
        'payable-reopened' => 'Conta voltou para pendente.',
        default => $status,
    } : null;
@endphp

@if ($message)
    <div
        x-data="{ visible: true }"
        x-show="visible"
        x-init="setTimeout(() => visible = false, 5000)"
        x-transition.opacity.duration.300ms
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="fixed left-4 right-4 top-4 z-[100] sm:left-auto sm:right-6 sm:max-w-md"
    >
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl shadow-slate-900/10">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="m5 12 4 4L19 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <p class="flex-1 pt-1.5 text-sm font-medium leading-5 text-slate-700">{{ $message }}</p>
            <button
                type="button"
                @click="visible = false"
                aria-label="Fechar notificação"
                class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06L11.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 1 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                </svg>
            </button>
        </div>
    </div>
@endif
