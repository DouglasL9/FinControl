<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Contexto do projeto

Este repositório contém o **FinControl**, uma aplicação Laravel para gestão compartilhada de contas a pagar recorrentes por empresa e unidade. A linha de base funcional e técnica está em [`docs/PROJETO.md`](docs/PROJETO.md); o [README.md](README.md) contém a visão rápida e a inicialização local.

Antes de alterar uma funcionalidade, consulte a documentação pertinente e confirme o comportamento no código atual. Não trate metas, cronograma, decisões ou itens marcados como `[Premissa]` como requisitos aprovados.

## Atualização obrigatória da documentação

Analise o impacto documental em toda alteração. Atualize `docs/PROJETO.md` e, quando aplicável, `README.md` na mesma entrega sempre que mudar comportamento, tela, regra de negócio, rota, modelo de dados, autorização, integração, scheduler, ambiente ou operação. Mantenha diagramas, requisitos, critérios de aceite, riscos e premissas coerentes com o código. Se não houver impacto documental, registre essa conclusão na descrição da alteração quando o fluxo de trabalho permitir.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
