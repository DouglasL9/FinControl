<x-app-layout>
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Central de ajuda</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Como usar o FinControl</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">Um guia prático para acompanhar as contas da empresa e organizar o trabalho da equipe.</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-primary">
                    Voltar ao perfil
                </a>
            </div>
        </header>

        <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
            <nav aria-label="Índice da documentação" class="h-fit rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm lg:sticky lg:top-6">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Neste guia</p>
                <ul class="flex gap-2 overflow-x-auto lg:flex-col">
                    <li><a href="#primeiros-passos" class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">Primeiros passos</a></li>
                    <li><a href="#contratos" class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">Cadastrar contratos</a></li>
                    <li><a href="#parcelas" class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">Acompanhar parcelas</a></li>
                    <li><a href="#equipe" class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">Empresa e equipe</a></li>
                    <li><a href="#perfil" class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">Perfil e segurança</a></li>
                </ul>
            </nav>

            <div class="min-w-0 space-y-6">
                <section id="primeiros-passos" class="scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Comece por aqui</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Primeiros passos</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Cada empresa tem seu próprio espaço, unidades, contratos e equipe.</p>

                    <ol class="mt-5 grid gap-3 md:grid-cols-3">
                        <li class="rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">1</span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">Crie ou escolha a empresa</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Use o seletor no menu para criar ou alternar entre empresas. Proprietários também podem reativar empresas arquivadas por esse seletor.</p>
                        </li>
                        <li class="rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">2</span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">Cadastre as unidades</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Adicione a sede e as filiais para separar os compromissos por local. Ao informar o CEP, rua, bairro, cidade e UF são preenchidos automaticamente; confira os dados antes de salvar.</p>
                        </li>
                        <li class="rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">3</span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">Registre os contratos</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Informe fornecedor, unidade, valor e vencimento para acompanhar as parcelas.</p>
                        </li>
                    </ol>
                </section>

                <section id="contratos" class="scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Contas a pagar</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Cadastrar contratos recorrentes</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Na tela Contas a pagar, use o formulário “Novo contrato mensal”. Escolha a unidade, informe o valor, o dia de vencimento e a vigência.</p>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <h3 class="text-sm font-semibold text-slate-800">Geração das parcelas</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">O sistema cria as ocorrências previstas para até três meses. Contratos mais distantes geram parcelas quando entram nesse período.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <h3 class="text-sm font-semibold text-slate-800">Pausar ou retomar</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Pausar interrompe novas parcelas, mas não apaga as que já existem. Retomar volta a gerar ocorrências elegíveis.</p>
                        </div>
                    </div>
                </section>

                <section id="parcelas" class="scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Acompanhamento</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Acompanhar parcelas e pagamentos</h2>
                    <ul class="mt-5 space-y-3">
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                            <p class="text-sm leading-6 text-slate-600"><strong class="font-semibold text-slate-800">Encontre uma conta:</strong> pesquise por descrição, fornecedor ou unidade e filtre por status ou sede.</p>
                        </li>
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                            <p class="text-sm leading-6 text-slate-600"><strong class="font-semibold text-slate-800">Edite o valor:</strong> abra “Ver parcelas” e salve quando o valor tiver sido alterado. Contas pagas ficam bloqueadas para edição.</p>
                        </li>
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                            <p class="text-sm leading-6 text-slate-600"><strong class="font-semibold text-slate-800">Registre o pagamento:</strong> marque a parcela como paga quando ela for quitada. Para reabri-la, confirme a ação; ela voltará a pendente e poderá ser editada.</p>
                        </li>
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                            <p class="text-sm leading-6 text-slate-600"><strong class="font-semibold text-slate-800">Consulte o histórico:</strong> expanda o histórico da parcela para ver alterações de valor e status, com responsável e data.</p>
                        </li>
                    </ul>
                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                            <p class="text-sm font-semibold text-amber-900">Pendente</p>
                            <p class="mt-1 text-sm leading-5 text-amber-800">Ainda não foi paga e vence hoje ou depois.</p>
                        </div>
                        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4">
                            <p class="text-sm font-semibold text-rose-900">Em atraso</p>
                            <p class="mt-1 text-sm leading-5 text-rose-800">Não foi paga e o vencimento já passou.</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                            <p class="text-sm font-semibold text-emerald-900">Paga</p>
                            <p class="mt-1 text-sm leading-5 text-emerald-800">Foi registrada como quitada.</p>
                        </div>
                    </div>
                </section>

                <section id="equipe" class="scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Colaboração</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Empresa e equipe</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Na tela Configurações, cada seção aparece conforme o seu acesso: Empresa (dados cadastrais), Identidade visual, Perfis de acesso, Equipe, Unidades e fornecedores e Arquivar empresa. Na seção Equipe, o proprietário pode buscar usuários cadastrados ou enviar convite por e-mail; o convite expira em sete dias. O proprietário também pode arquivar a empresa confirmando a senha atual na janela de confirmação; os dados ficam preservados, o acesso da equipe é suspenso e a reativação fica disponível no seletor.</p>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <h3 class="text-sm font-semibold text-slate-800">Proprietário</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Pode convidar pessoas, editar os dados cadastrais e personalizar cores, logo e marca d’água da empresa.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <h3 class="text-sm font-semibold text-slate-800">Membro</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Pode acompanhar e operar contratos e parcelas, mas não pode convidar pessoas nem alterar a identidade visual.</p>
                        </div>
                    </div>
                </section>

                <section id="perfil" class="scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">Sua conta</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Perfil e segurança</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Use o controle de tema no topo da tela para alternar entre modo claro e noturno. Sua escolha fica salva neste navegador; antes da primeira escolha, o sistema acompanha o tema do dispositivo.</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">No perfil, você pode atualizar nome e e-mail, alterar sua senha e solicitar a exclusão da conta. A exclusão é permanente e exige confirmação pela senha.</p>
                    <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary hover:brightness-90">
                        Ir para meu perfil <span aria-hidden="true">→</span>
                    </a>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
