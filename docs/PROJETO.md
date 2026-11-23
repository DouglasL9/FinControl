# FinControl — Especificação do Projeto

> **Documento de referência funcional, técnica e operacional**  
> **Versão:** 1.2 · **Data da linha de base:** 20/11/2026 · **Última revisão:** 23/11/2026 · **Idioma:** Português (Brasil)  
> **Estado:** linha de base elaborada a partir do código disponível; metas, prazos e requisitos identificados como premissas precisam de validação pelos stakeholders.

## Como interpretar esta documentação

- **Implementado no código** descreve comportamento observado nos arquivos do projeto; não significa que houve homologação, teste de ponta a ponta ou aceite de negócio.
- **[Premissa]** identifica uma decisão ou meta sugerida porque não foi fornecida pelo solicitante.
- **A confirmar** identifica uma lacuna que exige decisão de produto, negócio ou operação.
- Os diagramas Mermaid e os wireframes são representações conceituais. Não são capturas de tela nem contratos de API.

### Identificação e fontes

| Item | Linha de base |
|---|---|
| Nome | **[Premissa] FinControl**, nome apresentado na navegação e nas telas atuais. |
| Categoria | Aplicação web de controle compartilhado de contas a pagar recorrentes. |
| Código de referência | Controllers em [app/Http/Controllers](../app/Http/Controllers), serviços em [app/Services](../app/Services), modelos em [app/Models](../app/Models), rotas em [routes](../routes) e telas em [resources/views](../resources/views). |
| Versões declaradas | PHP `^8.3`, Laravel `^13.17`; dependências front-end e testes conforme [package.json](../package.json) e [composer.json](../composer.json). |
| Ambiente conhecido | [.env.example](../.env.example) sugere SQLite, sessão/cache/fila em banco e transporte de e-mail `log`; isso é referência de desenvolvimento, não configuração de produção. |
| Estado de implantação | **A confirmar.** Não há evidência nesta especificação de ambiente produtivo, SLA, volume, orçamento ou equipe contratada. |

---

## 1. Visão geral e objetivos

### 1.1 Problema

Empresas com mais de uma unidade precisam manter visibilidade sobre compromissos recorrentes, datas de vencimento, pagamentos realizados e valores pendentes. Controlar esses dados por planilhas ou mensagens dificulta a identificação de atrasos, a distribuição de responsabilidades e a leitura consolidada por unidade.

### 1.2 Proposta de valor

Centralizar em um espaço compartilhado por empresa:

1. os contratos que originam compromissos mensais;
2. as parcelas previstas em uma janela móvel de três meses;
3. o status e o valor de cada conta;
4. os totais gerais e por unidade;
5. o acesso de proprietários e membros convidados.

O FinControl registra e acompanha contas. **Não executa pagamentos bancários nem substitui um sistema contábil.**

### 1.3 Objetivos do produto

- Reduzir lançamentos repetitivos de despesas mensais por meio da geração automática de parcelas.
- Dar visibilidade sobre montantes em aberto, a vencer, pagos no mês e vencidos.
- Permitir que membros autorizados acompanhem e atualizem contas da empresa.
- Separar os dados e as operações por empresa ativa e unidade.
- Tornar explícitos os casos de vencimento, pagamento, convite expirado e geração duplicada.

### 1.4 Stakeholders e personas

| Persona / stakeholder | Necessidade principal | Capacidades observadas |
|---|---|---|
| Proprietário da empresa | Configurar o espaço e controlar acesso da equipe. | Criar empresa, gerenciar unidades, convidar membros e consultar/operar contas. |
| Membro da empresa | Acompanhar compromissos financeiros e atualizar pagamentos. | Consultar e operar contas e unidades da empresa; não pode convidar pessoas. |
| Responsável financeiro/operacional | Identificar vencimentos e priorizar pagamentos. | **[Premissa]** Atua como membro; não existe papel especializado de financeiro no modelo atual. |
| Usuário convidado | Acessar uma empresa com identidade validada. | Aceitar convite enviado para o seu e-mail, se estiver autenticado com esse mesmo endereço. |
| Gestor / patrocinador | Acompanhar adoção, riscos e resultado do produto. | **[Premissa]** Consome indicadores operacionais a definir; não há painel de gestão separado. |
| Equipe de desenvolvimento/operação | Manter aplicação, dados, e-mail, scheduler e documentação. | Operação técnica conforme ambiente e processos a serem definidos. |

### 1.5 Indicadores de sucesso (KPIs)

Não há evidência de telemetria de produto, metas aprovadas ou histórico de baseline. Os valores abaixo são **[Premissa] metas iniciais para discussão**, e não resultados medidos.

| KPI proposto | Definição / cálculo | Meta inicial [Premissa] | Instrumentação / fonte |
|---|---|---:|---|
| Parcelas geradas sem duplicidade | Parcelas recorrentes únicas por contrato e vencimento ÷ parcelas geradas. | 100% | Restrição única já existe no banco; monitorar falhas de execução. |
| Execuções diárias concluídas | Execuções do comando `payables:generate` concluídas ÷ execuções esperadas. | ≥ 99% ao mês | Log estruturado, alerta e histórico do scheduler; ainda a estabelecer. |
| Cobertura de geração | Contratos ativos com parcelas existentes até o horizonte esperado ÷ contratos ativos elegíveis. | ≥ 99% | Comparação periódica entre contratos e parcelas; ainda a estabelecer. |
| Redução de contas vencidas | Variação da quantidade/valor vencido contra baseline definido no piloto. | Redução ≥ 20% após 90 dias | Baseline do piloto e série temporal; não disponível atualmente. |
| Tempo de resposta de telas principais | Percentil 95 das requisições do painel e da listagem. | ≤ 2 s sob carga acordada | APM/teste de carga; **[Premissa]** capacidade e perfil de carga a definir. |
| Convites aceitos | Convites aceitos ÷ convites válidos enviados. | Definir após piloto | Eventos de convite e aceite; hoje o banco registra estados, sem painel analítico. |

**Critério de gestão:** antes de assumir compromissos, aprovar definição, fonte, periodicidade, responsável e baseline de cada KPI. Não interpretar a interface atual como instrumento de auditoria ou monitoramento de SLA.

### 1.6 Premissas e restrições

- **[Premissa]** O produto é destinado inicialmente a empresas brasileiras e apresenta moeda em reais (R$).
- **[Premissa]** A aplicação é entregue como monólito Laravel com renderização server-side; o navegador é o cliente principal.
- Há um contexto de empresa ativa na sessão. O modelo atual suporta vários vínculos de usuário-empresa.
- Os endpoints de negócio exigem autenticação e, na maioria das rotas, verificação de e-mail.
- O agendador precisa estar ativo para que a geração diária além da criação imediata do contrato seja executada.
- O modelo de dinheiro usa `decimal(12,2)`; a validação de formulário limita o valor a `9.999.999.999,99`.
- **A confirmar:** moeda e fuso horário oficiais, jurisdição e política de retenção/exclusão de dados.
- **A confirmar:** provedores de e-mail, banco, hospedagem, backups, RPO/RTO, SLA, orçamento, quantidade de empresas/usuários e equipe disponível.
- **A confirmar:** requisitos regulatórios, trilha de auditoria, aprovação dupla, anexos e exportações.

---

## 2. Escopo e personas

### 2.1 Escopo funcional atual

1. **Identidade e acesso:** cadastro, login, validação por código de seis dígitos enviado por e-mail, verificação de endereço, recuperação de senha, perfil e saída.
2. **Empresas e contexto:** criar e alternar empresas; o proprietário pode arquivar uma empresa com confirmação de senha, suspendendo o acesso da equipe sem apagar os dados, e reativá-la pelo seletor.
3. **Unidades:** cadastrar e editar unidades/sedes com endereço brasileiro (CEP, rua, número, complemento, bairro, cidade, UF e telefone); arquivar ou reativar sem apagar contratos e parcelas.
4. **Fornecedores:** manter cadastro independente por empresa com nome obrigatório e CNPJ, contato, e-mail, telefone, endereço e observações opcionais; arquivar ou reativar preservando os vínculos.
5. **Equipe:** busca de usuários cadastrados e envio de convites por e-mail, aceitação autenticada e registro de papel proprietário, administrador ou membro. O aceite associa o convidado como membro ao perfil de convite da empresa (“Membro”, somente consulta), nunca ao “Administrador”.
6. **Contratos recorrentes:** cadastrar descrição, fornecedor e unidade ativos da empresa, valor mensal, dia de vencimento, início e término opcional; pausar/retomar geração.
7. **Parcelas:** gerar ocorrências mensais até três meses à frente, editar o valor da ocorrência, marcar como paga, confirmar antes de reabrir e consultar o histórico de alteração do valor e do status, com responsável e data.
8. **Consulta:** pesquisar descrição, fornecedor ou unidade; filtrar por status; paginar resultados. Unidades arquivadas permanecem disponíveis nos filtros históricos.
9. **Painel:** apresentar totais globais e por unidade e uma lista curta de próximos vencimentos.
10. **Identidade visual:** proprietário (ou quem tem a permissão `company.update_branding`) configura cores, logo e opacidade da marca d’água; as alterações são compartilhadas no espaço autenticado da empresa.
11. **Aparência:** alternar entre modo claro e noturno em qualquer tela; a preferência é salva no navegador e, sem escolha manual, segue o tema do sistema.
12. **Perfis de acesso:** proprietários, administradores e quem tem a permissão `profiles.manage` criam perfis reutilizáveis com permissões por ação (painel, contas a pagar, unidades, fornecedores e gestão da empresa) e os atribuem aos membros pelo botão **Gerenciar** da lista de membros, que abre uma modal com o perfil de acesso e, para o proprietário, o papel do membro. Cada empresa nasce com dois perfis: “Administrador” (`is_default`, usado pelo criador; não pode ser editado nem excluído e fica oculto para quem não o possui) e “Membro” (`is_invite_default`, badge “Padrão para convites”; somente `dashboard.view`, `payables.view`, `units.view` e `suppliers.view`; só o proprietário o edita e ele não pode ser excluído).
13. **Dados cadastrais da empresa:** nome fantasia, razão social, CNPJ, e-mail, telefone e endereço, editáveis pelo proprietário na seção “Empresa” das configurações.
14. **Perfil Dev e administrador do sistema:** o perfil de acesso “Dev” e a coluna `users.is_system_admin` identificam quem pode consultar a documentação técnica em `/documentation`. O perfil Dev é oculto e protegido para quem não é administrador do sistema (seção 5.1).
15. **Configurações da empresa em seções:** a tela `/company` divide-se em Empresa, Identidade visual, Perfis de acesso, Equipe, Cadastros (unidades e fornecedores) e Arquivar; cada seção aparece conforme o acesso do usuário (identidade visual com `company.update_branding`, perfis com `profiles.manage`, equipe com `team.view` ou proprietário; a tela abre para quem tem qualquer permissão de gestão, de unidades ou de fornecedores).
16. **Meu perfil:** dados pessoais, senha, aviso da verificação em duas etapas por e-mail, lista “Minhas empresas” (papel, perfil de acesso, empresa ativa e arquivada) e exclusão de conta em zona de risco, com navegação por âncoras.
17. **Ajuda para usuários:** guia em linguagem simples em `/help`, com atalhos e seções por tarefa; a seção “Para desenvolvedores”, com o link da documentação técnica, só aparece para perfil Dev ou administrador do sistema.
18. **E-mails com a marca da empresa:** o código de login e o convite usam modelos próprios; a marca é a logo/nome da empresa do convite ou a única empresa ativa do usuário, senão o nome do aplicativo (`APP_NAME=FinControl` no `.env.example`).

### 2.2 Fora de escopo nesta linha de base

Os itens abaixo não aparecem como funcionalidade implementada no código examinado. São **fora de escopo da fase descrita**, salvo aprovação de mudança:

- Iniciar ou liquidar pagamentos, conectar a bancos, cartões, PIX, Open Finance ou gateways.
- Contabilidade, contas a receber, faturamento, orçamento, centro de custo ou conciliação bancária.
- Emissão fiscal, importação automática de notas/faturas, anexos ou armazenamento de comprovantes.
- Exportação para CSV/PDF/Excel, relatórios customizados ou integração com ERP.
- Aprovação de pagamentos, limites por usuário ou papel dedicado de contador. (Permissões por ação existem desde a versão 1.1 por meio dos perfis de acesso; as permissões de gestão da empresa, desde a 1.2.)
- Notificações de vencimento, lembretes de atraso ou alertas financeiros ao usuário final.
- Gestão de contratos não mensais, pagamentos parciais, parcelamento manual, juros, multas ou recorrência semanal/anual.
- Auditoria de alterações em contratos/equipe e trilha de eventos financeiros além das mudanças de valor e status das parcelas.
- Administração global de tenants, suporte interno, cobrança por assinatura e planos SaaS.
- Aplicativo móvel nativo e API pública para terceiros.

Qualquer inclusão exige análise de impacto em interface, modelo de dados, autorização, requisitos, testes, operação e cronograma.

### 2.3 Matriz de permissões observada

O acesso combina **papel** (`owner`, `admin`, `member`) e **perfil de acesso** (conjunto de permissões por ação). O proprietário tem sempre todas as permissões; administradores e membros seguem o perfil que lhes foi atribuído. O papel `admin` garante, além do perfil, `team.view`, `team.assign_profiles` e `profiles.manage` (o acesso que já tinha antes do grupo de gestão). **Anti-escalada:** quem não é proprietário só cria, edita ou atribui perfis contidos nas próprias permissões; não altera o próprio perfil, o do proprietário, o de administradores nem o de membros com mais permissões; não edita perfis em uso pelo proprietário ou por outro administrador; perfis ocultos continuam protegidos.

| Ação | Proprietário | Administrador | Membro |
|---|:---:|:---:|:---:|
| Consultar painel, contas, unidades e fornecedores | Sim | Conforme o perfil | Conforme o perfil |
| Criar contrato, ajustar parcela, marcar paga/reabrir, pausar contrato | Sim | Conforme o perfil | Conforme o perfil |
| Cadastrar, editar, arquivar e reativar unidade ou fornecedor | Sim | Conforme o perfil | Conforme o perfil |
| Criar/alternar entre empresas ativas de que participa | Sim | Sim | Sim |
| Visualizar membros da equipe (`team.view`) | Sim | Sim | Conforme o perfil |
| Criar, editar e excluir perfis de acesso (`profiles.manage`) | Sim | Sim (limitado às próprias permissões) | Conforme o perfil (limitado às próprias permissões) |
| Atribuir perfil a um membro (`team.assign_profiles`) | Sim (a qualquer não proprietário) | Somente a membros, sem exceder as próprias permissões | Conforme o perfil: somente a outros membros, sem exceder as próprias permissões |
| Designar administrador / alterar papel | Sim | Não | Não |
| Pesquisar usuários e enviar convite | Sim | Não | Não |
| Editar identidade visual (`company.update_branding`) | Sim | Conforme o perfil | Conforme o perfil |
| Editar dados cadastrais | Sim | Não | Não |
| Arquivar e reativar empresa | Sim | Não | Não |
| Aceitar convite para outra empresa | Sim, se convidado | Sim, se convidado | Sim, se convidado |
| Ver, editar, excluir ou atribuir o perfil Dev | Somente administrador do sistema | Somente administrador do sistema | Não |
| Consultar a documentação em `/documentation` | Se for Dev ou administrador do sistema | Se for Dev ou administrador do sistema | Se for Dev |
| Remover outro membro | Não implementado | Não implementado | Não implementado |

**Permissões disponíveis (18):** `dashboard.view`; `payables.view`, `payables.create_contract`, `payables.update_amount`, `payables.change_payment_status`, `payables.manage_contract`; `units.view`, `units.create`, `units.update`, `units.archive`; `suppliers.view`, `suppliers.create`, `suppliers.update`, `suppliers.archive`; gestão da empresa: `team.view`, `team.assign_profiles`, `profiles.manage`, `company.update_branding`. Habilitar uma ação habilita também a consulta do módulo correspondente (`team.assign_profiles` habilita `team.view`). As permissões de gestão nascem desativadas no perfil “Administrador” e no perfil de convite “Membro”.

**Observação:** o catálogo é fixo no código (`CompanyPermissionProfile::GROUPS`). Perfis são por empresa; o nome é único dentro da empresa.

### 2.4 Fluxos principais

#### Fluxo A — primeiro acesso

1. Visitante cria usuário com nome, e-mail e senha.
2. A aplicação envia a verificação de endereço pelo fluxo padrão do Laravel.
3. Usuário informa e-mail e senha na tela de login.
4. Após autenticação inicial, o sistema cria um desafio e envia um código de seis dígitos por e-mail (modelo com a marca e saudação pelo primeiro nome).
5. Usuário informa o código em até 10 minutos; são permitidas até cinco tentativas inválidas.
6. Sessão autenticada é criada; o acesso ao painel exige também endereço verificado.
7. Sem empresa, o painel solicita a criação do primeiro espaço.

#### Fluxo B — cadastrar e acompanhar uma despesa recorrente

1. Usuário cria ou atualiza uma unidade e cadastra o fornecedor para a empresa.
2. Informa contrato, selecionando um fornecedor e uma unidade ativos, além do valor, dia de vencimento e vigência.
3. O sistema gera as parcelas elegíveis até o fim do mês atual mais dois meses.
4. A listagem apresenta parcelas e contratos; os totais são recalculados a partir dos dados.
5. Usuário ajusta valor individual ou marca uma conta como paga; cada alteração de valor e transição de pagamento fica registrada na parcela com responsável e horário.
6. O vencimento é classificado automaticamente como pendente ou em atraso; pagamento prevalece sobre a data.

#### Fluxo E — personalizar o espaço da empresa

1. Proprietário (ou membro com `company.update_branding`) escolhe cor principal, cor de destaque, logo e opacidade da marca d’água.
2. O sistema valida e armazena as preferências na empresa ativa; a própria logo é usada no menu, no cabeçalho da modal de parcelas e como marca d’água.
3. Os demais membros veem a identidade compartilhada, mas não podem alterá-la.

#### Fluxo C — compartilhar empresa

1. Proprietário pesquisa pessoa cadastrada ou digita e-mail.
2. Sistema valida a autorização, registra/atualiza convite e envia link com token.
3. O convidado autentica-se com o e-mail exato do convite (comparação sem distinção entre maiúsculas/minúsculas).
4. Se válido e não expirado, aceita convite; transação associa usuário como membro com o perfil de convite (`is_invite_default`) e ativa a empresa na sessão. Se a empresa não tiver perfil de convite, o membro entra sem perfil (nenhuma permissão) até o proprietário atribuir um.
5. Em seguida, o proprietário ou um administrador pode ajustar o perfil do novo membro (Fluxo F).

#### Fluxo F — gerenciar acesso de um membro

1. Em Configurações > Equipe, o proprietário, o administrador ou o membro com `team.assign_profiles` clica em **Gerenciar** na linha do membro.
2. A modal “Gerenciar acesso” mostra o **perfil de acesso** (proprietário e administrador, este somente para membros) e o **papel na empresa** (somente proprietário: Membro ou Administrador).
3. Cada formulário é enviado separadamente (“Salvar perfil” e “Atualizar papel”) e o servidor revalida o papel de quem altera e o escopo da empresa.
4. O proprietário não possui o botão. Quem não pode gerenciar determinado membro também não o vê.

#### Fluxo G — consultar a documentação técnica

1. Usuário com o perfil Dev, ou administrador do sistema, abre o dropdown do usuário e escolhe **Documentação**.
2. O sistema exibe este documento (`docs/PROJETO.md`) com índice lateral, filtro de seções e destaque da seção em leitura; o cabeçalho tem subtítulo para a equipe técnica e o botão **Guia para usuários** (`/help`). Demais usuários recebem 403.
3. Também é possível chegar pela seção recolhida “Para desenvolvedores” da ajuda.

#### Fluxo D — alternar empresa

1. Usuário seleciona empresa de que já participa.
2. O servidor valida o vínculo e grava o identificador da empresa na sessão.
3. Painel e consultas subsequentes são resolvidos para essa empresa; seleção não autorizada é rejeitada.

---

## 3. Arquitetura e componentes

### 3.1 Visão lógica

```mermaid
flowchart LR
    U[Usuário no navegador] -->|HTTPS / formulários| W[Rotas web Laravel]
    W --> M[Middleware auth, verified, throttle]
    M --> C[Controllers]
    C --> S[Serviços de domínio]
    C --> E[Eloquent Models]
    S --> E
    E --> DB[(Banco relacional)]
    C --> V[Blade Views]
    V --> U
    C --> N[Laravel Notifications]
    N --> MAIL[Transporte de e-mail]
    SCH[Laravel Scheduler] --> CMD[payables:generate]
    CMD --> S
```

### 3.2 Camadas e responsabilidades

| Componente | Responsabilidade | Referências do projeto |
|---|---|---|
| Rotas web | Definem páginas, operações POST/PATCH e middlewares. | [routes/web.php](../routes/web.php), [routes/auth.php](../routes/auth.php), [routes/console.php](../routes/console.php) |
| Controllers | Validam requisições, aplicam escopo da empresa, coordenam serviços e retornam Blade/redirect/erros HTTP. | [app/Http/Controllers](../app/Http/Controllers) e [app/Http/Controllers/Auth](../app/Http/Controllers/Auth) |
| Serviços | Centralizam geração de parcelas e agregações do painel. | [RecurringPayableGenerator.php](../app/Services/RecurringPayableGenerator.php), [PayableSummary.php](../app/Services/PayableSummary.php) |
| Modelos Eloquent | Representam usuário, empresa, unidade, contrato, parcela, desafio de login e convite. | [app/Models](../app/Models) |
| Views | Renderizam páginas e formulários com Blade. | [dashboard.blade.php](../resources/views/dashboard.blade.php), [resources/views/payables](../resources/views/payables), [resources/views/companies](../resources/views/companies), [resources/views/auth](../resources/views/auth) |
| Front-end | Estilos Tailwind, compilação Vite e interatividade leve Alpine.js. | [resources/css](../resources/css), [resources/js](../resources/js), [vite.config.js](../vite.config.js) |
| Persistência | Relacionamentos, índices, chaves estrangeiras, unicidade de recorrências e valores decimais. | [database/migrations](../database/migrations) |
| Notificações | Envio de código de login e convite por e-mail, em modelos Markdown com a marca da empresa (logo ou nome) e saudação pelo primeiro nome; o trait `BuildsBrandedMail` escolhe a marca. | [app/Notifications](../app/Notifications), [resources/views/mail](../resources/views/mail), [resources/views/vendor/mail](../resources/views/vendor/mail) |
| Execução periódica | Gera parcelas pendentes diariamente às 01:00, sem sobreposição agendada. | [routes/console.php](../routes/console.php), [GenerateUpcomingPayables.php](../app/Console/Commands/GenerateUpcomingPayables.php) |

### 3.3 Diagrama de dados conceitual

```mermaid
erDiagram
    USERS ||--o{ COMPANY_USER : participa
    COMPANIES ||--o{ COMPANY_USER : possui
    COMPANIES ||--o{ COMPANY_PERMISSION_PROFILES : define
    COMPANY_PERMISSION_PROFILES |o--o{ COMPANY_USER : concede
    COMPANIES ||--o{ UNITS : organiza
    COMPANIES ||--o{ SUPPLIERS : cadastra
    COMPANIES ||--o{ RECURRING_PAYABLES : define
    UNITS ||--o{ RECURRING_PAYABLES : classifica
    SUPPLIERS o|--o{ RECURRING_PAYABLES : fornece
    RECURRING_PAYABLES ||--o{ PAYABLES : gera
    UNITS ||--o{ PAYABLES : classifica
    USERS o|--o{ PAYABLES : registra_pagamento
    PAYABLES ||--o{ PAYABLE_HISTORIES : registra
    USERS o|--o{ PAYABLE_HISTORIES : altera
    COMPANIES ||--o{ COMPANY_INVITATIONS : convida
    USERS o|--o{ COMPANY_INVITATIONS : envia
    USERS ||--o| LOGIN_CHALLENGES : verifica

    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        boolean is_system_admin
    }
    COMPANIES {
        bigint id PK
        string name
        string legal_name
        char cnpj
        string email
        string phone
        text address
        boolean is_active
        string primary_color
        string accent_color
        string logo_path
        unsignedTinyInteger watermark_opacity
    }
    COMPANY_USER {
        bigint company_id PK, FK
        bigint user_id PK, FK
        string role
        bigint permission_profile_id FK
    }
    COMPANY_PERMISSION_PROFILES {
        bigint id PK
        bigint company_id FK
        string name
        json permissions
        boolean is_default
        boolean is_invite_default
    }
    UNITS {
        bigint id PK
        bigint company_id FK
        string name
        string postal_code
        string street
        string number
        string complement
        string neighborhood
        string city
        char state
        string phone
        boolean is_active
    }
    SUPPLIERS {
        bigint id PK
        bigint company_id FK
        string name
        char cnpj
        string contact_name
        string email
        string phone
        text address
        text notes
        boolean is_active
    }
    RECURRING_PAYABLES {
        bigint id PK
        bigint company_id FK
        bigint unit_id FK
        bigint supplier_id FK
        string provider
        decimal amount
        unsignedTinyInteger due_day
        date starts_on
        date ends_on
        boolean is_active
    }
    PAYABLES {
        bigint id PK
        bigint company_id FK
        bigint unit_id FK
        bigint recurring_payable_id FK
        bigint paid_by_user_id FK
        decimal amount
        date due_date
        timestamp paid_at
    }
    PAYABLE_HISTORIES {
        bigint id PK
        bigint payable_id FK
        bigint user_id FK
        string actor_name
        string action
        decimal old_amount
        decimal new_amount
        timestamp created_at
    }
    COMPANY_INVITATIONS {
        bigint id PK
        bigint company_id FK
        string email
        string token_hash UK
        timestamp expires_at
        timestamp accepted_at
    }
    LOGIN_CHALLENGES {
        bigint id PK
        bigint user_id FK, UK
        string code_hash
        unsignedTinyInteger attempts
        timestamp expires_at
    }
```

**Integridade relevante:** empresas, unidades e fornecedores são arquivados em vez de excluídos pela interface. Apenas o proprietário pode arquivar/reativar uma empresa; o arquivamento exige senha, suspende o acesso de todos os membros e mantém contratos, parcelas, históricos, convites e identidade visual. Empresas arquivadas não geram parcelas e convites pendentes não podem ser aceitos; o proprietário pode reativar pelo seletor. Unidades têm nome único por empresa; fornecedores são isolados por empresa e CNPJ, quando informado, é único nessa empresa. Fornecedores e unidades arquivados não podem ser usados em novos contratos, mas permanecem disponíveis para consulta histórica. A migração cria um fornecedor por nome de fornecedor legado em cada empresa e liga os contratos existentes; o nome gravado em cada parcela permanece como fotografia histórica. Associação empresa-usuário tem chave composta; convites são únicos por empresa/e-mail; parcelas são únicas por contrato/data de vencimento.

### 3.4 Fluxos de dados técnicos

#### Autenticação em duas etapas por e-mail

```mermaid
sequenceDiagram
    actor Pessoa
    participant Web as Laravel Web
    participant DB as Banco
    participant Mail as Transporte de e-mail
    Pessoa->>Web: E-mail e senha
    Web->>DB: Validar credenciais e criar desafio
    Web->>Mail: Enviar código de 6 dígitos
    Web-->>Pessoa: Tela de verificação
    Pessoa->>Web: Código
    Web->>DB: Validar hash, validade e tentativas
    alt Código válido e não expirado
        Web->>DB: Remover desafio e criar sessão
        Web-->>Pessoa: Redirecionar ao destino/painel
    else Inválido, expirado ou tentativas esgotadas
        Web-->>Pessoa: Erro e retorno ao login quando necessário
    end
```

#### Geração de parcelas recorrentes

```mermaid
flowchart TD
    A[Contrato ativo] --> B[Calcular mês inicial e horizonte: mês atual + 2]
    B --> C{Mês dentro da vigência?}
    C -- Não --> G[Encerrar]
    C -- Sim --> D[Calcular vencimento: dia configurado limitado ao último dia do mês]
    D --> E{Vencimento dentro de starts_on / ends_on?}
    E -- Sim --> F[Localizar ou criar parcela por contrato + vencimento]
    E -- Não --> H[Não criar parcela nesse mês]
    F --> I[Avançar um mês]
    H --> I
    I --> C
```

O serviço é acionado ao cadastrar contrato e pelo comando diário. A chave única no banco torna repetida a execução segura contra duplicidade; pausar contrato impede novas gerações, sem apagar as parcelas já existentes.

### 3.5 Decisões técnicas observadas e implicações

- **Monólito MVC:** interface Blade e regras no servidor; não há API pública identificada.
- **Isolamento por empresa ativa:** consultas de contas e contratos partem do relacionamento da empresa corrente. Não há evidência de bancos ou schemas separados por empresa.
- **Autorização por papel e perfil:** middleware exige usuário e e-mail verificado nas rotas de negócio. Convites, papéis, dados cadastrais e arquivamento verificam o papel `owner`; equipe, perfis e identidade visual verificam as permissões de gestão (`Company::allows`, com as garantias do papel `admin`) e as regras anti-escalada `Company::canDelegate`, `canAssignProfileTo` e `canEditProfile`; as operações de negócio verificam a permissão do perfil de acesso do membro. O proprietário tem todas as permissões.
- **Perfil Dev e administrador do sistema:** o escopo `CompanyPermissionProfile::visibleTo` oculta o perfil cujo nome é “Dev” (sem diferenciar maiúsculas e minúsculas) de quem não é administrador do sistema; criação, edição, exclusão, atribuição e renomeação para esse nome também são bloqueadas no servidor. A flag `users.is_system_admin` não é preenchível por formulário e só é alterada diretamente no banco.
- **Documentação interna:** a rota `/documentation` converte `docs/PROJETO.md` em HTML no servidor (HTML bruto removido), gera âncoras e índice e é restrita a perfil Dev ou administrador do sistema.
- **Fila e e-mail:** [.env.example](../.env.example) define fila em banco e e-mail em `log` (e `APP_NAME=FinControl`), mas as notificações observadas não são enfileiradas explicitamente. Os e-mails de login e de convite usam `->markdown()` com os modelos `mail.login-code` e `mail.company-invitation` e o tema publicado em `resources/views/vendor/mail`. A operação real depende do transporte configurado.
- **Agendamento:** frequência diária às 01:00 com `withoutOverlapping`; não há evidência nesta linha de base de alertas de falha ou monitoramento do scheduler.
- **Idempotência:** `firstOrCreate` associado à unicidade `recurring_payable_id + due_date`.
- **Valores:** persistidos como decimal; apresentação usa formatação brasileira e R$.
- **Estado de pagamento:** `paid_at` e `paid_by_user_id` representam o estado atual; as alterações de valor e transições de pagamento também são registradas em `payable_histories`, com ator e horário.
- **Identidade visual:** cores, logo e opacidade são configuradas por empresa. A logo é um ativo público de marca e só aceita PNG, JPEG ou WebP de até 2 MB; não enviar imagens com informação confidencial.
- **Autenticação:** desafio de login armazena hash do código, expira em 10 minutos e limita a cinco tentativas. O código é enviado por e-mail.

### 3.6 Mapa de telas

| Tela | Rota principal | Conteúdo e ações |
|---|---|---|
| Página pública | `/` | Apresentação e acesso a cadastro/login. |
| Cadastro / Login / Verificação | `/register`, `/login`, `/login/verify` | Identidade, credenciais e código enviado por e-mail em layout público responsivo compartilhado. |
| Visão geral | `/dashboard` | Cabeçalho em destaque, cartões financeiros, próximos vencimentos, totais por unidade ou formulário inicial para criar empresa. |
| Contas a pagar | `/payables` | Cabeçalho destacado, resumo, totais por unidade, cadastro de contrato, pausa/retomada, busca, filtros, parcelas, pagamentos e histórico de alterações por parcela. |
| Empresa (configurações) | `/company` | Cabeçalho “Espaço da empresa” e seções por abas conforme o acesso: dados cadastrais, identidade visual, perfis de acesso, equipe (convites e lista de membros com a modal **Gerenciar**), unidades e fornecedores e, para o proprietário, arquivar a empresa mediante senha. |
| Aceitar convite | `/company-invitations/{token}` | Resumo da empresa e aceite autenticado do convite. |
| Perfil | `/profile` | “Meu perfil”: cabeçalho, resumo da conta com selo de e-mail verificado, atalho para a ajuda, barra de âncoras (Dados pessoais, Segurança, Minhas empresas, Zona de risco), dados pessoais, senha, aviso da verificação em duas etapas por e-mail, lista “Minhas empresas” (papel Proprietário/Administrador/Membro, perfil de acesso e marcas “Empresa ativa” e “Arquivada”; `ProfileController::edit` envia `memberships`) e exclusão de conta. |
| Documentação técnica | `/documentation` | Este documento com índice lateral e filtro, subtítulo para a equipe técnica e botão “Guia para usuários”; somente perfil Dev ou administrador do sistema, acessível pelo dropdown do usuário. |
| Ajuda | `/help` | “Como usar o FinControl”, em linguagem simples para o usuário final: boas-vindas, três atalhos, barra de seções e seis cartões (primeiros passos, contas a pagar, empresa e equipe, quem pode fazer o quê, acesso e segurança, dúvidas frequentes). A seção recolhida “Para desenvolvedores” só aparece para perfil Dev ou administrador do sistema. Acessível a partir do perfil e da documentação. |
| Aparência | Global | Alternância entre modo claro e noturno, com preferência salva no navegador. |

### 3.7 Wireframes conceituais

#### Visão geral / painel

```text
+-----------------------------------------------------------------------+
| FinControl     Visão geral   Contas a pagar   Empresa   [Perfil]      |
+-----------------------------------------------------------------------+
| Painel financeiro                         [mês / ano] [Contas a pagar]|
| Olá, [nome]                                                          |
| Acompanhe os compromissos financeiros da [empresa]                   |
|                                                                       |
| [ Total em aberto ] [ A vencer ] [ Pagas neste mês ] [ Em atraso ]   |
|                                                                       |
| Próximos vencimentos                        | Totais por unidade      |
| [dia] Descrição / fornecedor / unidade      | Unidade                 |
|        Status                         Valor  | Em aberto / pagas / atraso |
| ...                                          | ...                     |
+-----------------------------------------------------------------------+
```

#### Contas a pagar

```text
+-----------------------------------------------------------------------+
| Gestão financeira                          [Gerenciar equipe]         |
| Contas a pagar — [empresa]                                           |
| [Total em aberto] [A vencer] [Pagas no mês] [Em atraso]              |
| Resumo por unidade: cartões responsivos com contagem de contratos e totais |
|                                                                       |
| Novo contrato (4 colunas)       | Contratos e parcelas (8 colunas)   |
| descrição, fornecedor           | [Busca] [Status] [Unidade] [Filtrar]|
| unidade, valor, vencimento      | contrato / unidade / valor          |
| início, término                 | [Pausar / Ver parcelas]             |
| [Cadastrar contrato]            |                                     |
+-----------------------------------------------------------------------+
```

#### Empresa e equipe

```text
+-----------------------------------------------------------------------+
| Espaço da empresa: [nome] [unidades] [membros]                        |
| Identidade visual: [Cor principal] [Cor de destaque] [Logo]           |
| [Opacidade da marca d’água__________] [Prévia] [Salvar]                |
|                                                                       |
| [Criar outra empresa]               | [Unidades e sedes]              |
| [Nome________________] [Criar]      | [Dados de endereço e contato]   |
|                                      | [Adicionar] [Editar / arquivar] |
| [Fornecedores] cadastro e detalhes  | [Editar / arquivar / reativar]  |
| [Convidar equipe]                   | [Membros da empresa]            |
| [Buscar usuário por nome/e-mail]    | [Lista rolável de membros]      |
| [E-mail________________] [Enviar]   |                                 |
| Convites pendentes                  |                                 |
+-----------------------------------------------------------------------+

Membros da empresa (lista compacta):
| [Avatar] Nome / e-mail                       [Papel]  [Gerenciar]     |
Modal “Gerenciar acesso”:
| Perfil de acesso  [seletor________] [Salvar perfil]                   |
| Papel na empresa  [Membro/Admin___] [Atualizar papel]  (só proprietário)|
```

#### Perfil e telas de acesso

```text
Perfil:
| Cabeçalho em destaque: Meu perfil                                    |
| [Avatar] [Nome / e-mail] [Status de verificação]  [Abrir guia]        |
| [Dados pessoais] [Segurança] [Minhas empresas] [Zona de risco]        |
| [Informações pessoais]            | [Senha] [Verificação em 2 etapas] |
| [Minhas empresas: nome · papel · perfil · Empresa ativa / Arquivada]   |
| [Excluir conta — área de risco, separada dos formulários]              |

Cadastro / login / recuperação:
| FinControl                         | [Título e descrição da tela]     |
| Compromissos e unidades organizados| [Formulário responsivo]          |
|                                     | [Ação principal]                 |
| Em telas estreitas, a marca e o formulário ficam em um cartão único.  |

Ajuda (/help):
| Como usar o FinControl                          [Voltar ao perfil]   |
| [Estou começando] [Quero acompanhar uma conta] [Quero chamar alguém] |
| [Primeiros passos] [Contas] [Equipe] [Permissões] [Segurança] [FAQ]  |
| Cartões por tarefa; [Para desenvolvedores] recolhido (só Dev/admin)  |

Documentação (/documentation, só Dev/admin do sistema):
| Índice lateral/rolável  | Conteúdo de PROJETO.md   [Guia para usuários] |
```

---

## 4. Requisitos funcionais e não funcionais

Prioridades: **P0** = essencial para operação do escopo atual; **P1** = importante para segurança/qualidade operacional; **P2** = evolução recomendada. “Implementado no código” não significa aceite de produção.

### 4.1 Requisitos funcionais

| ID | Prioridade | Requisito / situação observada | Critérios de aceite |
|---|---|---|---|
| RF01 | P0 | Criar conta com nome, e-mail único e senha confirmada; **implementado no código**. | Dados inválidos não criam conta; e-mail deve estar em minúsculas e ser único; cadastro encaminha para login e fluxo de verificação. |
| RF02 | P0 | Autenticar por senha e código de e-mail de seis dígitos; **implementado no código**. | Código correto, vigente e ligado à sessão conclui login; código inválido não autentica; desafio expira em 10 min e deixa de valer após cinco tentativas inválidas. |
| RF03 | P0 | Criar, alternar e arquivar empresas; **implementado no código**. | Criador vira proprietário; empresa escolhida deve pertencer ao usuário; apenas proprietário arquiva com senha; arquivamento mantém dados e suspende acesso; proprietário reativa pelo seletor. |
| RF04 | P0 | Cadastrar, editar e arquivar unidade da empresa ativa; **implementado no código**. | Nome obrigatório e único dentro da empresa; campos de endereço e telefone são opcionais; CEP aceita oito dígitos com máscara; UF aceita duas letras; arquivamento é reversível, preserva referências e impede novos contratos. |
| RF05 | P0 | Convidar membro para a empresa; **implementado no código**. | Apenas proprietário pesquisa/convida; convite associa e-mail normalizado, token não armazenado em texto puro e validade de sete dias. |
| RF06 | P0 | Aceitar convite; **implementado no código**. | Usuário autenticado com o mesmo e-mail do convite e token válido é associado como membro; convite é consumido em transação e empresa passa a ativa. |
| RF07 | P0 | Cadastrar contrato recorrente; **implementado no código**. | Descrição obrigatória; fornecedor e unidade devem estar ativos e pertencer à empresa ativa; valor positivo com até duas casas; dia entre 1 e 31; término não anterior ao início. |
| RF08 | P0 | Gerar parcelas mensais para contratos elegíveis; **implementado no código**. | Geração contempla início e janela até mês atual + 2; vigência é inclusiva; dia 31 ajusta ao último dia do mês; repetir operação não duplica parcela. |
| RF09 | P0 | Pausar e retomar contrato; **implementado no código**. | Pausado não gera novas parcelas; parcelas anteriores permanecem; retomar volta a gerar dentro da vigência e sem duplicar. |
| RF10 | P0 | Consultar, pesquisar, filtrar e paginar contas; **implementado no código**. | Busca considera descrição, fornecedor e unidade; filtros incluem todos, a vencer, pagas e atrasadas; listagem mostra 20 registros por página e mantém filtros ao paginar. |
| RF11 | P0 | Ajustar valor de uma parcela; **implementado no código**. | Valor maior que zero, limitado a duas casas e ao máximo definido; apenas parcela da empresa ativa pode ser alterada. |
| RF12 | P0 | Marcar conta como paga ou reabrir; **implementado no código**. | Marcar registra instante e usuário; a interface confirma antes de reabrir, o que limpa ambos os campos; atualização só alcança conta da empresa ativa. |
| RF13 | P0 | Exibir resumo geral e por unidade; **implementado no código**. | Painel calcula aberto, a vencer, pago no mês e em atraso; totais correspondem a parcelas e à data corrente; lista até seis próximos vencimentos pendentes. |
| RF14 | P0 | Gerar parcelas diariamente; **implementado no código; operação externa depende do scheduler**. | Agendamento chama `payables:generate` diariamente às 01:00; não inicia execução sobreposta; comando informa quantidade criada; contratos de empresas arquivadas não geram parcelas. |
| RF15 | P1 | Atualizar perfil e excluir conta com confirmação de senha; **implementado no código**. | E-mail alterado perde marcação de verificado; exclusão exige senha; propriedade é transferida para membro remanescente ou empresa sem membros é removida. |
| RF16 | P1 | Personalizar identidade visual da empresa; **implementado no código**. | Proprietário ou membro com `company.update_branding` altera cores, logo e opacidade; tema aparece para membros no espaço autenticado; logo aceita PNG/JPEG/WebP até 2 MB; opacidade de 0 a 30%; remoção ou substituição limpa o arquivo anterior. |
| RF17 | P0 | Cadastrar, editar e arquivar fornecedores por empresa; **implementado no código**. | Nome obrigatório; CNPJ, contato, e-mail, telefone, endereço e observações opcionais; CNPJ normalizado e único por empresa; arquivamento reversível preserva contratos vinculados. |
| RF18 | P0 | Selecionar fornecedor cadastrado no contrato recorrente; **implementado no código**. | Apenas fornecedor ativo da empresa pode ser selecionado; alteração do fornecedor atualiza o contrato e as próximas parcelas, sem reescrever o nome histórico já gravado nas parcelas existentes. |
| RF19 | P1 | Arquivar e reativar empresa; **implementado no código**. | Apenas proprietário pode arquivar e deve confirmar a senha atual; a equipe perde acesso, dados e convites permanecem guardados, convites não são aceitos durante o arquivamento e a empresa pode ser reativada pelo seletor. |
| RF20 | P1 | Alternar o tema visual; **implementado no código**. | Controle de tema disponível nas telas públicas, de autenticação e autenticadas; preferência manual persiste no navegador, e a preferência do sistema é usada antes da primeira escolha. |
| RF21 | P1 | Gerenciar perfis de acesso e atribuí-los a membros; **implementado no código**. | Proprietário, administrador ou perfil com `profiles.manage`/`team.assign_profiles`; quem não é proprietário só atribui a outros membros e não concede, edita nem atribui perfis além das próprias permissões; perfil “Administrador” não pode ser editado nem excluído; perfil de convite só é editado pelo proprietário e não pode ser excluído; perfil em uso não pode ser excluído; nome único por empresa; permissões válidas e consistentes com a consulta do módulo. |
| RF22 | P1 | Editar dados cadastrais da empresa; **implementado no código**. | Apenas proprietário; nome obrigatório; CNPJ com 14 dígitos (máscara ignorada); e-mail válido; telefone até 32 e endereço até 1000 caracteres. |
| RF23 | P1 | Restringir e exibir o perfil Dev e a documentação; **implementado no código**. | Perfil Dev invisível e imutável para quem não é administrador do sistema, inclusive por requisição direta; `/documentation` devolve 403 a quem não é Dev nem administrador do sistema; o link só aparece para quem tem acesso. |
| RF24 | P1 | Listar no perfil as empresas do usuário e seu nível de acesso; **implementado no código**. | “Minhas empresas” mostra, por empresa, papel (Proprietário/Administrador/Membro), nome do perfil de acesso quando houver, “Empresa ativa” e “Arquivada”; sem vínculos, exibe mensagem vazia; o aviso de verificação em duas etapas cita o e-mail da conta. |
| RF25 | P1 | Oferecer ajuda em linguagem de usuário final; **implementado no código**. | `/help` exige login e traz as seis seções por tarefa; “Para desenvolvedores” e o link da documentação só aparecem para perfil Dev ou administrador do sistema. |
| RF26 | P1 | Enviar e-mails de login e convite com a marca da empresa; **implementado no código**. | Mensagens em modelo Markdown com logo ou nome da empresa (padrão: nome do aplicativo); convite informa validade de 7 dias; código informa validade de 10 minutos. |

### 4.2 Requisitos não funcionais

| ID | Prioridade | Requisito / estado | Critérios de aceite |
|---|---|---|---|
| RNF01 | P0 | Isolamento entre empresas; **implementado por escopo de relacionamento, a validar por testes de segurança**. | Usuário não lê nem altera unidade, contrato, convite ou parcela de empresa alheia; requests cruzados retornam recusa e não alteram dados. |
| RNF02 | P0 | Proteção de autenticação; **controles parciais no código**. | Senhas seguem hash do framework; código não é persistido em texto puro; expiração, limite de tentativas e throttling permanecem cobertos por testes. |
| RNF03 | P0 | Integridade e idempotência financeira; **restrições presentes no schema**. | Valores mantêm precisão decimal; chaves únicas e estrangeiras impedem duplicação/órfãos conforme as regras; alterações transacionais não deixam estado parcial. |
| RNF04 | P1 | Desempenho de telas; **meta a confirmar**. | **[Premissa]** P95 ≤ 2 s para painel/listagem em carga representativa aprovada; paginação impede carregamento ilimitado. |
| RNF05 | P1 | Compatibilidade responsiva e acessibilidade; **layout responsivo aparente; conformidade não auditada**. | Operações essenciais funcionam em celular e desktop; campos têm rótulos, erros são apresentados e fluxos principais podem ser usados por teclado; **[Premissa]** objetivo WCAG 2.2 AA. |
| RNF06 | P1 | Disponibilidade e recuperação; **não definido**. | Aprovar SLA, backups, retenção, RPO/RTO e teste de restauração antes de produção; configuração de ambiente é documentada sem expor segredos. |
| RNF07 | P1 | Observabilidade; **lacuna operacional a fechar**. | Execução agendada, falha de e-mail e erro de aplicação são rastreáveis; alertas têm responsável e procedimento de resposta. |
| RNF08 | P1 | Manutenibilidade e regressão; **fundação presente**. | Mudanças críticas têm teste automatizado adequado; migrations são reversíveis quando possível; documentação compatível com a versão implantada. |
| RNF09 | P1 | Governança documental; **regra definida neste documento**. | Mudança de requisito, fluxo, tela, dado, integração, segurança ou operação revisa este documento e o README na mesma entrega. |
| RNF10 | P2 | Escalabilidade; **limites e capacidade não definidos**. | Antes de ampliar volume, medir consultas/agregações, frequência do scheduler, conexões e infraestrutura contra perfil aprovado. |
| RNF11 | P1 | Segurança de ativos de marca; **implementado no código**. | Uploads exigem autenticação, proprietário ou permissão `company.update_branding`, validação de tipo/tamanho e não aceitam SVG; arquivos ficam no disco público apenas por serem imagens de marca. |

---

## 5. Regras de negócio e casos de borda

### 5.1 Empresas e autorização

1. Uma pessoa pode participar de múltiplas empresas; o contexto ativo é mantido na sessão.
2. Ao criar empresa, o usuário criador recebe papel `owner`; aceite de convite cria papel `member` com o perfil de convite da empresa (“Membro”, somente consulta).
3. Se a empresa ativa registrada na sessão não pertencer mais ao usuário, o método de resolução tenta a primeira empresa associada por identificador.
4. A operação das contas, unidades e fornecedores depende das permissões do perfil de acesso do membro. Somente proprietário pode pesquisar usuários, emitir convites, designar administradores, editar dados cadastrais e arquivar a empresa. Proprietário e administrador gerenciam perfis de acesso; membros comuns também podem ver a equipe, gerenciar e atribuir perfis e alterar a identidade visual quando o perfil concede as permissões de gestão correspondentes.
5. O escopo das consultas deve sempre começar pela empresa ativa. IDs enviados pelo navegador não substituem a validação de pertencimento.
6. Ao excluir conta, a propriedade de cada empresa é transferida ao membro remanescente de menor ID; se não houver sucessor, a empresa é excluída.
7. Não existe fluxo administrativo para remover membro ou transferir propriedade manualmente além da regra de exclusão de conta. O papel de um membro pode ser alterado apenas entre `member` e `admin`, e o proprietário não pode ter papel ou perfil alterados.
8. A identidade visual pertence à empresa ativa e é compartilhada com todos os membros. Somente o proprietário e quem tem `company.update_branding` podem alterá-la; ao excluir o último proprietário e a empresa, a logo armazenada também é removida.
9. **Perfil Dev:** o nome “Dev” é reservado. Quem não é administrador do sistema não o vê em listas ou seletores, não o cria nem renomeia outro perfil para esse nome (“Este nome é reservado”), e não o edita, exclui ou atribui (404/403). Membros que já usam o perfil Dev também não podem ter perfil ou papel alterados por quem não é administrador do sistema.
10. **Administrador do sistema:** definido por `users.is_system_admin`; vê e gerencia o perfil Dev e acessa a documentação. Não há tela para marcar administradores: a alteração é feita no banco.

### 5.2 Convites

1. E-mail é normalizado para minúsculas; um usuário já associado à empresa não pode ser convidado novamente.
2. Convite é único por empresa/e-mail. Reenvio para o mesmo endereço atualiza token, remetente, expiração e estado de aceite do convite existente.
3. Token tem 64 caracteres aleatórios; somente seu hash SHA-256 é armazenado. Link válido expira em sete dias.
4. Convite aceito, expirado ou token inexistente não pode ser utilizado novamente.
5. E-mail autenticado deve corresponder ao e-mail convidado, sem distinção de caixa; divergência retorna acesso negado.
6. Busca de usuário requer pelo menos dois caracteres e limita resultados a oito. Busca e envio têm limites de requisição por rota.
7. Falha de entrega do e-mail depende do transporte configurado; ambiente `log` não entrega mensagens a uma caixa real.

### 5.3 Contratos e parcelas

1. Valor deve ser maior que zero e ter até duas casas decimais; limites de formulário e precisão de banco devem permanecer consistentes.
2. `due_day` varia de 1 a 31. Para mês mais curto, o vencimento usa o último dia existente (por exemplo, dia 31 em fevereiro vira dia 28 ou 29).
3. A primeira ocorrência só é criada se a data calculada for igual ou posterior a `starts_on`.
4. Se `ends_on` estiver preenchido, a data de vencimento precisa ser igual ou anterior a ele; o mês final é inclusivo.
5. A janela de geração termina no último dia do segundo mês seguinte ao mês corrente, totalizando até três meses de calendário.
6. Alteração de valor de uma parcela não modifica automaticamente contrato nem demais parcelas.
7. Pausar contrato impede novas parcelas, mas não cancela parcelas existentes. Como o gerador percorre a vigência desde `starts_on`, retomar (ou executar a rotina diária após uma falha) pode criar parcelas ausentes de meses anteriores, inclusive já vencidos, dentro do horizonte elegível; a unicidade impede duplicações. **A confirmar:** esse comportamento de recuperação retroativa é desejado ou meses perdidos devem ser ignorados?
8. Uma parcela recorrente é identificada para unicidade pelo contrato e data de vencimento.
9. **A confirmar:** mudança de valor/data/unidade de um contrato deve afetar apenas novas parcelas ou também as existentes? Não há edição do contrato implementada nesta linha de base.
10. Alterações no valor e ações de marcar paga/reabrir criam eventos com valores anterior/novo quando aplicável, nome do responsável e horário. A trilha só cobre ações feitas após a implantação; alterações anteriores não podem ser reconstruídas.

### 5.4 Estados da conta e indicadores

| Condição | Estado exibido | Efeito em totais |
|---|---|---|
| `paid_at` preenchido | Paga | Não compõe aberto/a vencer/em atraso; compõe pago no mês se quitada no mês corrente. |
| Não paga e vencimento anterior a hoje | Em atraso | Compõe aberto e em atraso. |
| Não paga e vencimento hoje ou futuro | Pendente / a vencer | Compõe aberto e a vencer. |

- O estado **paga prevalece** sobre vencimento já passado.
- Um vencimento na data corrente continua pendente; só passa a atraso no dia seguinte segundo o relógio/fuso da aplicação.
- “Pagas neste mês” considera `paid_at` dentro do mês calendário corrente.
- Os valores agregados vêm das parcelas, não da soma nominal dos contratos.
- Reabrir uma conta limpa data e usuário no estado atual, preservando a transição e o responsável no histórico da parcela.

### 5.5 Tratamento de erros esperado

| Situação | Comportamento observado/esperado |
|---|---|
| Campo inválido | Validação impede persistência e devolve erros junto ao formulário. |
| Empresa, unidade ou parcela fora do escopo | Recurso não é encontrado no relacionamento autorizado (tipicamente 404). |
| Ação exclusiva de proprietário por membro | Acesso negado (403). |
| Convite com token inválido/expirado/consumido | Convite não é localizado como válido. |
| E-mail diferente do destinatário do convite | Acesso negado; convite não é aceito. |
| Código inválido de login | Erro no campo; tentativa incrementada. Ao atingir cinco tentativas, desafio é removido e fluxo retorna ao login. |
| Código expirado | Desafio é removido ou invalidado e o usuário retorna ao login. |
| Falha no agendador ou e-mail | Depende de log/configuração operacional; alerta automático não está confirmado e deve ser definido. |
| Perfil Dev manipulado por quem não é administrador do sistema | Perfil não é localizado (404) ou a atribuição/alteração é negada (403); nome reservado devolve erro de validação. |
| Acesso a `/documentation` sem perfil Dev nem administrador do sistema | Acesso negado (403). |
| Logo inválida ou usuário sem `company.update_branding` | Upload é rejeitado pela validação ou atualização retorna 403; cores/configurações não são alteradas. |

### 5.6 Identidade visual da empresa

1. Cor principal e cor de destaque usam valores hexadecimais de seis dígitos; novas empresas iniciam com `#4f46e5` e `#7c3aed`. A opacidade inicial da marca d’água é 6%.
2. A logo é opcional, aceita PNG, JPEG ou WebP até 2 MB e é armazenada no disco público; arquivos SVG são rejeitados.
3. A logo aparece no menu e no cabeçalho da modal de parcelas, e pode ser exibida como marca d’água; a opacidade configurável varia de 0% a 30%, e 0% oculta a marca d’água.
4. As cores e a logo são aplicadas às telas autenticadas da empresa ativa; telas públicas/autenticação usam a identidade padrão do FinControl.
5. Ao substituir ou remover logo, o arquivo antigo é removido. Ao excluir uma empresa sem sucessor, sua logo também é limpa.
6. As configurações de identidade visual existem a partir desta implantação; não há migração de logos ou temas anteriores.

---

## 6. Cronograma, fases e marcos

O projeto não forneceu prazo, equipe, orçamento nem data de implantação. O plano abaixo é uma **[Premissa] proposta relativa de seis semanas**, iniciada após aprovação do escopo e alocação dos responsáveis. Não representa compromisso de entrega.

| Fase | Janela [Premissa] | Atividades | Entregáveis / marco de saída | Dependências |
|---|---|---|---|---|
| 0. Linha de base e decisões | Semana 1 | Validar personas, moeda, fuso, papéis, metas, dados e fora de escopo; revisar esta especificação com stakeholders. | **M0:** escopo, KPIs e decisões pendentes aprovados. | Patrocinador e responsável de produto disponíveis. |
| 1. Qualidade funcional e técnica | Semanas 1–2 | Executar testes; validar isolamento por empresa, OTP, convites, geração de meses-limite e cálculos; corrigir gaps acordados. | Relatório de testes, backlog priorizado e release candidata. | M0, ambiente de teste e dados não produtivos. |
| 2. Preparação operacional | Semana 3 | Configurar banco, e-mail, timezone, scheduler, logs, backups e alertas; testar falha e restauração. | **M1:** checklist operacional e homologação técnica. | Acesso à infraestrutura/provedores e requisitos RPO/RTO. |
| 3. Homologação e piloto | Semana 4 | UAT por proprietário/membro; validar fluxos e textos; iniciar piloto com empresas representativas. | **M2:** aceite de negócio e baseline de KPIs do piloto. | M1, usuários piloto e suporte definidos. |
| 4. Liberação controlada | Semana 5 | Implantação progressiva, monitoramento de erros, e-mail, geração e desempenho; comunicação e suporte. | **M3:** liberação autorizada com plano de rollback. | M2, backups e procedimento de incidente. |
| 5. Estabilização | Semana 6 | Revisar indicadores, erros e solicitações; atualizar requisitos, operação e treinamento. | **M4:** relatório pós-implantação e backlog da próxima fase. | M3 e disponibilidade dos stakeholders. |

### Marcos de governança

- Não iniciar produção sem configuração de e-mail real, scheduler monitorado, backups testados e responsáveis de suporte.
- Não marcar uma fase como aceita apenas porque a aplicação compilou; validar critérios funcionais e evidências acordadas.
- Alterações de escopo após M0 devem registrar impacto em prazo, risco, dados, testes e documentação.

---

## 7. Riscos e mitigações

Escalas qualitativas iniciais **[Premissa]**: probabilidade e impacto — Baixo, Médio, Alto. Reavaliar após definição da infraestrutura e piloto.

| Risco | Impacto | Probabilidade | Plano de ação / mitigação |
|---|---|---|---|
| Scheduler não executa ou falha sem alerta | Parcelas futuras não aparecem; painel fica incompleto. | Média | Configurar cron do Laravel por minuto, monitorar resultado do comando, alertar em falha e reconciliar contratos ativos diariamente. |
| Entrega de e-mail indisponível ou em spam | Login e aceite de convite ficam bloqueados. | Média | Configurar provedor/transporte de produção, SPF/DKIM/DMARC, monitorar bounce/latência e testar fluxo fim a fim. |
| Acesso cruzado entre empresas | Exposição ou alteração indevida de dados financeiros. | Média | Manter escopo por relação da empresa ativa, revisar cada endpoint e cobrir tentativas com IDs de outro tenant em testes. |
| Permissão de membro ampla demais | Operador pode alterar valor ou marcar conta sem aprovação. | Alta | Aprovar matriz de papéis; se necessário criar permissões granulares, fluxo de aprovação e trilha de auditoria antes do uso com controle interno rigoroso. |
| Auditoria de alterações incompleta | Alterações de valor e status de parcela são rastreáveis a partir da implantação; contratos, equipe e eventos anteriores não têm trilha completa. | Média | Definir retenção e o escopo adicional de auditoria; validar com usuários que o registro de ator, horário e valores atende à operação. |
| Falha de banco, backup ou restauração | Perda ou indisponibilidade de registros. | Média | Escolher banco produtivo, backups automatizados, criptografia e teste periódico de restauração com RPO/RTO aprovados. |
| Datas e horário divergentes | Vencimento ou execução diária classificados em dia incorreto. | Média | Confirmar fuso oficial, configurar timezone de aplicação/servidor, testar limites de mês e horário de verão se aplicável. |
| Precisão ou arredondamento de valores | Divergência financeira em telas e somatórios. | Baixa/Média | Preservar decimal no banco, não converter para ponto flutuante em regras de negócio, validar formatação/localização e casos de duas casas. |
| Volume aumenta e agregações degradam | Painel/listagem lentos ou timeout. | Média | Medir perfil real, revisar índices/consultas e introduzir cache/agregações só após evidência de gargalo e estratégia de invalidação. |
| Convite encaminhado ou usado por pessoa errada | Associação indevida à empresa. | Baixa/Média | Preservar token longo armazenado como hash, validade curta, validação do e-mail autenticado e limitação de requisições. |
| Expectativa de funcionalidades não cobertas | Adoção baixa ou uso incorreto como sistema bancário/contábil. | Média | Comunicar claramente que o produto controla compromissos, não processa pagamentos; validar fora de escopo com patrocinador. |
| Metas, orçamento e responsáveis indefinidos | Cronograma e aceite sem base objetiva. | Alta | Aprovar KPIs, equipe, orçamento, suporte e decisões pendentes durante a fase 0 antes de comprometer datas. |

---

## 8. Operação e manutenção da documentação

### 8.1 Rotina operacional mínima proposta

1. Confirmar saúde da aplicação, banco e transporte de e-mail.
2. Verificar execução do `payables:generate` após o horário agendado e investigar falhas/contagens anormais.
3. Acompanhar erros de autenticação, aceite de convites e acessos negados sem registrar tokens, códigos ou credenciais nos logs.
4. Fazer backup de acordo com política aprovada e testar restauração.
5. Revisar indicadores e amostras de contas com stakeholders durante o piloto.
6. Ter responsável, canal e procedimento de escalonamento para incidentes.

Esta rotina é recomendação para homologação/produção; a existência dos processos e das ferramentas não foi confirmada.

### 8.2 Regra obrigatória de atualização por mudança

Toda mudança de código deve ser analisada contra esta especificação e atualizada conforme o desenvolvimento. Antes de concluir uma alteração, verificar:

- **Funcionalidade/validação/regra:** atualizar seções 2, 4 e 5 e os critérios de aceite.
- **Rota, controller, serviço, job ou integração:** atualizar arquitetura, fluxo de dados e operação.
- **Tela, campo, navegação ou estado visual:** atualizar mapa de telas e wireframe correspondente.
- **Modelo, migration, enum, índice ou relacionamento:** atualizar diagrama de dados, regras e estratégia de migração.
- **Autorização, identidade, e-mail ou dado pessoal:** revisar matriz de permissões, requisitos de segurança, riscos e tratamento de erros.
- **Scheduler, fila, cache, storage, variáveis de ambiente ou deploy:** atualizar stack, inicialização, execução e checklist operacional; nunca registrar segredos.
- **Mudança de escopo ou cronograma:** atualizar fora de escopo, premissas, dependências, marcos e riscos.
- **Correção sem impacto documental:** registrar na revisão que o documento foi analisado e não exigiu mudança, quando o processo de PR/release permitir.

### 8.3 Histórico e revisão

| Versão | Data | Alteração |
|---|---|---|
| 1.0 | 20/11/2026 | Criação da linha de base de produto e arquitetura a partir do código existente; premissas e decisões pendentes explicitadas. |
| 1.1 | 23/11/2026 | Perfis de acesso e papel administrador na matriz de permissões; modal “Gerenciar” da equipe; dados cadastrais da empresa e configurações em seções; perfil Dev, administrador do sistema (`users.is_system_admin`) e página `/documentation`; ajuste do painel quando não há totais por unidade. |
| 1.2 | 23/11/2026 | Permissões de gestão da empresa (`team.view`, `team.assign_profiles`, `profiles.manage`, `company.update_branding`; 18 permissões) com regras anti-escalada; perfil “Acesso completo” renomeado para “Administrador”; perfil padrão de convite “Membro” (`is_invite_default`, somente consulta); tela “Meu perfil” com barra de âncoras e lista “Minhas empresas”; ajuda `/help` reescrita para usuários finais, com seção “Para desenvolvedores”; `/documentation` com subtítulo e botão “Guia para usuários”; e-mails de login e convite com a marca da empresa; testes novos em `ProfileTest`, `CompanyInviteProfileTest` e `CompanyManagementPermissionsTest`. |

**Operação da revisão 1.2:** nenhuma migration nova além das listadas abaixo (introduzidas no ciclo 1.1/1.2); para exibir a logo nos e-mails, o link `php artisan storage:link` precisa estar ativo, pois a logo é servida pelo disco público.

**Operação das migrations:** após atualizar o código, executar `php artisan migrate` (migrations de dados cadastrais da empresa, de `is_system_admin`, da renomeação do perfil “Acesso completo” para “Administrador”, das permissões de gestão, que entram desativadas em todos os perfis existentes, e do perfil de convite “Membro”, criado em cada empresa sem alterar perfis nem membros existentes; se já houver um perfil “Membro”, o novo recebe o nome “Membro (convite)”). Para designar um administrador do sistema, atualizar `users.is_system_admin` diretamente no banco.

**Responsável de aprovação:** **A confirmar** (produto/negócio e responsável técnico).  
**Próxima revisão recomendada:** após validar escopo, ambiente de implantação, KPIs e resultados do primeiro ciclo de homologação.
