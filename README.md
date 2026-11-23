# FinControl

Aplicação web para organizar contas a pagar recorrentes de empresas e suas unidades. O sistema permite acompanhar vencimentos, registrar pagamentos, visualizar totais financeiros e compartilhar o espaço de trabalho com uma equipe.

> **Estado do produto:** a descrição abaixo foi consolidada a partir do código existente. A documentação não representa, por si só, aceite formal, disponibilidade em produção ou validação de metas operacionais.

## Funcionalidades atuais

- Cadastro, login com código de verificação por e-mail e verificação de endereço de e-mail.
- Modo noturno com alternância manual e preferência salva no navegador.
- Cadastro e alternância entre empresas.
- Arquivamento reversível de empresas pelo proprietário, com senha; os dados são preservados e o acesso da equipe é suspenso.
- Identidade visual por empresa, com cores, logo e marca d’água no espaço autenticado.
- Cadastro e edição de unidades por empresa, com endereço brasileiro e arquivamento reversível.
- Cadastro de fornecedores por empresa, com dados de contato, CNPJ e arquivamento.
- Contratos recorrentes com geração idempotente de parcelas mensais.
- Pesquisa, filtros e paginação de contas a pagar.
- Ajuste do valor de uma parcela, marcação como paga e reabertura.
- Histórico por parcela de alterações de valor e status, com responsável e data.
- Seleção de fornecedor e unidade ativos nos novos contratos, preservando registros e parcelas existentes.
- Painel com valores em aberto, a vencer, pagos no mês e em atraso, incluindo visão por unidade.
- Convites de equipe por e-mail, com expiração em sete dias.
- Guia de uso do sistema acessível pela área de perfil.

## Documentação do produto

A especificação funcional e técnica, incluindo personas, telas esquemáticas, diagramas, requisitos, regras de negócio, cronograma, riscos e política de atualização documental, está em [docs/PROJETO.md](docs/PROJETO.md).

**Regra de manutenção:** toda alteração que mude comportamento, interface, dados, integrações, segurança, execução agendada ou operação deve ser analisada contra a especificação e refletida nela na mesma entrega.

## Tecnologias

- PHP `^8.3` e Laravel `^13.17`.
- Blade, Laravel Breeze, Alpine.js, Tailwind CSS e Vite.
- Eloquent ORM e migrations.
- Pest para testes.
- SQLite como banco indicado pelo `.env.example`; outros ambientes dependem da configuração local.
- E-mail via Notifications do Laravel; o transporte precisa ser configurado para cada ambiente.

## Inicialização local

1. Instale as dependências PHP e JavaScript.
2. Crie ou ajuste o arquivo `.env` a partir de `.env.example`; configure banco, chave da aplicação e transporte de e-mail.
3. Gere a chave, aplique as migrations e compile os ativos:

   ```sh
   composer install
   npm install
   php artisan key:generate
   php artisan migrate
   php artisan storage:link
   npm run build
   ```

4. Em terminais separados, inicie a aplicação e os ativos de desenvolvimento:

   ```sh
   php artisan serve
   npm run dev
   ```

5. Para validar localmente a geração diária de parcelas, mantenha o scheduler ativo:

   ```sh
   php artisan schedule:work
   ```

   Em produção, configure o agendador do Laravel para executar `php artisan schedule:run` a cada minuto. O comando de geração está programado para 01:00 e usa `withoutOverlapping`.

## Verificação

```sh
php artisan test
npm run build
```

Consulte [docs/PROJETO.md](docs/PROJETO.md) para os critérios de aceite, limites conhecidos e requisitos de operação. Credenciais e valores reais de `.env` não devem ser incluídos na documentação.
