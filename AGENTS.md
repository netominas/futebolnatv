<laravel-boost-guidelines>
# Decisões permanentes do projeto

- Portal brasileiro SSR em Laravel, Blade e Tailwind; não é uma SPA.
- A Wosti Futebol TV Brasil é a fonte principal e única de jogos nesta fase.
- Persistir e publicar somente eventos da Wosti que tenham ao menos um canal de TV ou streaming válido.
- Não complementar a grade com partidas sem transmissão vindas de outras APIs.
- Timezone `America/Sao_Paulo`, locale `pt_BR` e horários públicos de Brasília.
- A API nunca é consultada durante uma visita: Wosti → comando agendado → banco → Laravel → Blade.
- Credenciais vivem apenas no `.env`, nunca no Git.

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

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
