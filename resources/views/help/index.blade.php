@php
    $user = auth()->user();
    $company = $user?->currentCompany();
    $canSeeTechnicalDocs = $user !== null && (
        $user->isSystemAdmin()
        || ($company?->hasDeveloperProfile($company->membershipFor($user)) ?? false)
    );

    $icons = [
        'rocket' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.63 8.41m5.96 5.96a14.93 14.93 0 0 1-5.84 2.58m-.12-9.54a6 6 0 0 0-7.38 5.84h4.8m2.58-5.84a14.93 14.93 0 0 0-2.58 5.84m2.7 2.7a15.1 15.1 0 0 1-2.7-2.7',
        'receipt' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.59a1 1 0 0 1 .7.29l5.42 5.42a1 1 0 0 1 .29.7V19a2 2 0 0 1-2 2Z',
        'users' => 'M15 19.13a9.4 9.4 0 0 0 4.12-.9 4 4 0 0 0-7.1-2.6M15 19.13V19a6 6 0 0 0-.9-3.18M15 19.13a12 12 0 0 1-6 .87 12 12 0 0 1-6-1.87 6 6 0 0 1 11.1-3.18M12 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.5 1.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
        'key' => 'M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.03 5.91l-1.64 1.64H9.75v2.25H7.5v2.25H3.75v-2.8l6.12-6.12A6 6 0 1 1 21.75 8.25Z',
        'shield' => 'M9 12.75 11.25 15 15 9.75m-3-7.04A11.96 11.96 0 0 1 3.6 6 12 12 0 0 0 3 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15A11.95 11.95 0 0 1 12 2.71Z',
        'question' => 'M9.88 9.88a3 3 0 1 1 4.24 4.24c-.46.46-1.12.88-1.12 1.88m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'code' => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5',
    ];

    $sections = [
        'primeiros-passos' => 'Primeiros passos',
        'contas' => 'Contas a pagar',
        'equipe' => 'Empresa e equipe',
        'permissoes' => 'Quem pode fazer o quê',
        'seguranca' => 'Seu acesso e segurança',
        'duvidas' => 'Dúvidas frequentes',
    ];

    $sectionHeader = 'flex items-start gap-4';
    $sectionIcon = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-muted text-brand-primary';
    $card = 'scroll-mt-6 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7';
    $subtitle = 'mt-6 text-sm font-semibold uppercase tracking-[0.12em] text-slate-400';
    $svgAttributes = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Central de ajuda</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Como usar o FinControl</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">Bem-vindo! Aqui você encontra, em poucas palavras, como controlar as contas da empresa e trabalhar em equipe.</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-primary">
                    Voltar ao perfil
                </a>
            </div>
        </header>

        <section aria-label="Atalhos" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['#primeiros-passos', 'rocket', 'Estou começando', 'Crie a empresa e cadastre sua primeira conta.'],
                ['#contas', 'receipt', 'Quero acompanhar uma conta', 'Veja como conferir vencimentos e registrar pagamentos.'],
                ['#equipe', 'users', 'Quero chamar alguém', 'Convide pessoas e defina o que cada uma pode fazer.'],
            ] as [$href, $icon, $title, $text])
                <a href="{{ $href }}" class="group flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-brand-primary/40 hover:shadow-md">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand-primary">
                        <svg class="h-5 w-5" {!! $svgAttributes !!}><path d="{{ $icons[$icon] }}" /></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800 group-hover:text-brand-primary">{{ $title }}</span>
                        <span class="mt-0.5 block text-sm leading-5 text-slate-500">{{ $text }}</span>
                    </span>
                </a>
            @endforeach
        </section>

        <nav aria-label="Seções do guia" class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200/80 bg-white p-2 text-sm font-medium shadow-sm">
            @foreach ($sections as $anchor => $label)
                <a href="#{{ $anchor }}" class="shrink-0 rounded-xl px-4 py-2 text-slate-600 transition hover:bg-brand-muted hover:text-brand-primary">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="space-y-6">
            <section id="primeiros-passos" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['rocket'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Primeiros passos</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Cada empresa tem seu próprio espaço, com unidades, fornecedores, contratos e equipe. Em três passos você já começa a acompanhar suas contas.</p>
                    </div>
                </div>

                <ol class="mt-5 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        ['Crie ou escolha a empresa', 'Use o seletor no menu para criar uma empresa ou alternar entre as que você participa. Quem é proprietário também pode reativar empresas arquivadas por ali.'],
                        ['Cadastre unidades e fornecedores', 'Unidades são a sede e as filiais; fornecedores são quem recebe seus pagamentos. Ao informar o CEP, o endereço é preenchido sozinho, mas confira antes de salvar.'],
                        ['Registre as contas fixas', 'Em Contas a pagar, cadastre o valor mensal e o dia de vencimento. O FinControl cria as parcelas para você.'],
                    ] as $index => [$title, $text])
                        <li class="rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">{{ $index + 1 }}</span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 rounded-2xl border border-brand-soft bg-brand-muted p-4 text-sm leading-6 text-slate-700"><strong class="font-semibold text-slate-900">Dica:</strong> o painel inicial mostra os totais da empresa, os totais por unidade e os próximos vencimentos. É o melhor lugar para começar o dia.</p>
            </section>

            <section id="contas" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['receipt'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Contas a pagar</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Cadastre uma vez e acompanhe todo mês. Cada conta fixa vira uma parcela por mês.</p>
                    </div>
                </div>

                <h3 class="{{ $subtitle }}">Como cadastrar uma conta fixa</h3>
                <ol class="mt-3 space-y-3">
                    @foreach ([
                        'Abra Contas a pagar e use o formulário “Novo contrato mensal”.',
                        'Escolha a unidade e o fornecedor, e informe o valor mensal.',
                        'Defina o dia do vencimento e a data de início. Se a conta tiver fim, informe também.',
                        'Salve. As parcelas dos próximos três meses aparecem na lista; as seguintes surgem automaticamente quando chegarem a esse período.',
                    ] as $index => $text)
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white">{{ $index + 1 }}</span>
                            <p class="text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <h3 class="{{ $subtitle }}">No dia a dia</h3>
                <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['Encontrar uma conta', 'Pesquise pela descrição, fornecedor ou unidade e filtre por situação ou sede.'],
                        ['Corrigir um valor', 'Abra “Ver parcelas”, altere o valor da parcela e salve. Parcelas pagas ficam bloqueadas.'],
                        ['Registrar um pagamento', 'Marque a parcela como paga. Se foi um engano, reabra confirmando a ação: ela volta a pendente e pode ser editada.'],
                        ['Pausar ou retomar uma conta fixa', 'Pausar para de criar novas parcelas, mas mantém as que já existem. Ao retomar, elas voltam a ser criadas.'],
                        ['Ver o histórico', 'Expanda o histórico da parcela para saber quem mudou o valor ou a situação, e quando.'],
                    ] as [$title, $text])
                        <li class="rounded-2xl border border-slate-100 p-4">
                            <h4 class="text-sm font-semibold text-slate-800">{{ $title }}</h4>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $text }}</p>
                        </li>
                    @endforeach
                </ul>

                <h3 class="{{ $subtitle }}">O que significa cada situação</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                        <p class="text-sm font-semibold text-amber-900">Pendente</p>
                        <p class="mt-1 text-sm leading-5 text-amber-800">Ainda não foi paga e vence hoje ou mais adiante.</p>
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

            <section id="equipe" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['users'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Empresa e equipe</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Tudo isso fica em Configurações. Cada bloco aparece conforme o seu acesso: Empresa, Identidade visual, Perfis de acesso, Equipe, Unidades e fornecedores, e Arquivar empresa.</p>
                    </div>
                </div>

                <h3 class="{{ $subtitle }}">Como convidar alguém</h3>
                <ol class="mt-3 space-y-3">
                    @foreach ([
                        'Em Configurações, abra a seção Equipe (apenas o proprietário envia convites).',
                        'Procure a pessoa entre os usuários já cadastrados ou digite o e-mail dela.',
                        'Envie o convite. Ela recebe um e-mail com um link que vale por sete dias.',
                        'Ao aceitar, a pessoa entra com o perfil “Membro”, que só permite consultar. Depois, use o botão Gerenciar para dar mais acesso, se quiser.',
                    ] as $index => $text)
                        <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white">{{ $index + 1 }}</span>
                            <p class="text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-3 rounded-2xl border border-brand-soft bg-brand-muted p-4 text-sm leading-6 text-slate-700"><strong class="font-semibold text-slate-900">Atenção:</strong> a pessoa precisa entrar com o mesmo e-mail que recebeu o convite.</p>

                <h3 class="{{ $subtitle }}">Outras opções da empresa</h3>
                <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['Dados e identidade visual', 'O proprietário edita os dados cadastrais e personaliza cores, logo e marca d’água. Toda a equipe vê o resultado.'],
                        ['Arquivar unidades e fornecedores', 'Quem não é mais usado pode ser arquivado e reativado depois. Contratos e parcelas antigos continuam salvos.'],
                    ] as [$title, $text])
                        <li class="rounded-2xl border border-slate-100 p-4">
                            <h4 class="text-sm font-semibold text-slate-800">{{ $title }}</h4>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $text }}</p>
                        </li>
                    @endforeach
                    <li class="rounded-2xl border border-slate-100 p-4 sm:col-span-2">
                        <h4 class="text-sm font-semibold text-slate-800">Arquivar a empresa</h4>
                        <p class="mt-1 text-sm leading-5 text-slate-500">O proprietário pode arquivar a empresa confirmando a senha. Os dados são preservados, o acesso da equipe fica suspenso e a reativação é feita pelo seletor de empresas.</p>
                    </li>
                </ul>
            </section>

            <section id="permissoes" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['key'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Quem pode fazer o quê</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">O acesso de cada pessoa depende da função dela na empresa e do perfil de acesso que recebeu.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        ['Proprietário', 'Quem criou a empresa. Pode tudo: convidar pessoas, editar dados e identidade visual, gerenciar perfis e arquivar a empresa.'],
                        ['Administrador', 'Ajuda a organizar a equipe: vê os membros e cria perfis de acesso, sem poder dar mais acesso do que ele mesmo tem.'],
                        ['Membro', 'Usa o sistema conforme o perfil que recebeu. Por padrão, apenas consulta painel, contas, unidades e fornecedores.'],
                    ] as [$title, $text])
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>

                <h3 class="{{ $subtitle }}">Perfis de acesso</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Um perfil é um conjunto de permissões que você aplica a várias pessoas de uma vez. As permissões são organizadas por área:</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (['Painel', 'Contas a pagar', 'Unidades', 'Fornecedores', 'Gestão da empresa'] as $area)
                        <span class="rounded-full bg-brand-muted px-3 py-1.5 text-xs font-semibold text-brand-primary">{{ $area }}</span>
                    @endforeach
                </div>
                <ul class="mt-4 space-y-3">
                    <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                        <p class="text-sm leading-6 text-slate-600">Toda empresa já começa com dois perfis: <strong class="font-semibold text-slate-800">Administrador</strong> (usado por quem criou a empresa) e <strong class="font-semibold text-slate-800">Membro</strong> (o padrão para convidados).</p>
                    </li>
                    <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                        <p class="text-sm leading-6 text-slate-600">Para dar um perfil a alguém, abra Configurações, vá em Equipe e clique em <strong class="font-semibold text-slate-800">Gerenciar</strong> na linha da pessoa.</p>
                    </li>
                    <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-primary"></span>
                        <p class="text-sm leading-6 text-slate-600">Quem não é proprietário só consegue conceder acessos que já possui, e não altera o próprio perfil.</p>
                    </li>
                </ul>
            </section>

            <section id="seguranca" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['shield'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Seu acesso e segurança</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Para proteger os dados financeiros, o login tem uma etapa extra de confirmação.</p>
                    </div>
                </div>

                <ol class="mt-5 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        ['Entre com e-mail e senha', 'Use os dados cadastrados. Seu e-mail também precisa estar verificado para acessar o painel.'],
                        ['Digite o código do e-mail', 'Enviamos um código de seis dígitos para o seu e-mail. Ele vale por 10 minutos e você tem até cinco tentativas.'],
                        ['Pronto, você está dentro', 'Se o código expirar ou as tentativas acabarem, basta entrar novamente para receber um novo.'],
                    ] as $index => [$title, $text])
                        <li class="rounded-2xl bg-slate-50 p-4">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-muted text-sm font-semibold text-brand-primary">{{ $index + 1 }}</span>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 text-sm leading-6 text-slate-500">Em <a href="{{ route('profile.edit') }}" class="font-semibold text-brand-primary hover:brightness-90">Meu perfil</a> você atualiza nome e e-mail, troca a senha e pode solicitar a exclusão da conta (permanente, com confirmação por senha). O botão de tema no topo alterna entre modo claro e noturno; a escolha fica salva neste navegador.</p>
            </section>

            <section id="duvidas" class="{{ $card }}">
                <div class="{{ $sectionHeader }}">
                    <span class="{{ $sectionIcon }}"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['question'] }}" /></svg></span>
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Dúvidas frequentes</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Respostas rápidas para o que mais perguntam.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['Paguei uma conta por engano. Como corrijo?', 'Reabra a parcela e confirme a ação. Ela volta a pendente e pode ser editada de novo.'],
                        ['Por que não consigo editar uma parcela?', 'Parcelas pagas ficam bloqueadas. Reabra a parcela primeiro, ou confira se o seu perfil permite alterar valores.'],
                        ['O convite não chegou ou expirou. E agora?', 'Convites valem por sete dias. Peça ao proprietário para enviar um novo e verifique a caixa de spam.'],
                        ['Não vejo uma tela ou botão que um colega vê.', 'O acesso depende do perfil de cada pessoa. Peça a quem administra a equipe para ajustar o seu perfil.'],
                        ['O código de login não funciona.', 'Ele vale por 10 minutos e aceita até cinco tentativas. Entre novamente com e-mail e senha para receber outro.'],
                        ['Posso apagar uma unidade ou fornecedor?', 'Você pode arquivá-los. Assim deixam de aparecer nas novas escolhas, mas contratos e parcelas antigos continuam consultáveis.'],
                        ['O sistema envia lembretes de vencimento?', 'Ainda não. Use o painel e o filtro “Em atraso” para acompanhar o que precisa de atenção.'],
                    ] as [$question, $answer])
                        <details class="group rounded-2xl border border-slate-100 p-4 open:bg-slate-50">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                                {{ $question }}
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                            </summary>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            @if ($canSeeTechnicalDocs)
                <details class="group rounded-3xl border border-dashed border-slate-300 bg-white p-5 shadow-sm sm:p-6">
                    <summary class="flex cursor-pointer list-none items-center gap-4">
                        <span class="{{ $sectionIcon }} !bg-slate-100 !text-slate-500"><svg class="h-6 w-6" {!! $svgAttributes !!}><path d="{{ $icons['code'] }}" /></svg></span>
                        <span class="flex-1">
                            <span class="block font-semibold text-slate-900">Para desenvolvedores</span>
                            <span class="block text-sm text-slate-500">Visível apenas para o perfil Dev e administradores do sistema.</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                    </summary>
                    <p class="mt-4 text-sm leading-6 text-slate-500">A especificação completa do sistema (arquitetura, regras de negócio, requisitos e riscos) está na documentação técnica.</p>
                    <a href="{{ route('documentation.show') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary hover:brightness-90">
                        Abrir documentação técnica <span aria-hidden="true">→</span>
                    </a>
                </details>
            @endif
        </div>
    </div>
</x-app-layout>
