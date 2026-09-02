# Futebol na TV

Portal brasileiro para consultar jogos de futebol com transmissão confirmada na TV ou no streaming.

## Escopo

A Wosti Futebol TV Brasil é a fonte principal da programação. A aplicação importa somente eventos que tenham ao menos um canal válido; jogos sem transmissão não são armazenados como parte da grade pública.

O fluxo de dados é:

```text
Wosti → comando agendado → banco de dados → Laravel → páginas Blade
```

A página pública nunca consulta a API externa durante uma visita.

## Tecnologias

- PHP 8.4
- Laravel 13
- Blade e Tailwind CSS
- MySQL 8 em produção
- Redis para cache, filas e bloqueios do agendador
- Nginx e PHP-FPM

## Configuração da Wosti

Copie o `.env.example` para `.env` e preencha a chave sem versioná-la:

```dotenv
WOSTI_API_KEY=
WOSTI_API_HOST=wosti-futebol-tv-brasil.p.rapidapi.com
WOSTI_API_URL=https://wosti-futebol-tv-brasil.p.rapidapi.com
WOSTI_SYNC_ENABLED=true
WOSTI_SYNC_CRON="*/30 * * * *"
```

Importação manual:

```bash
php artisan wosti:sync-events
```

O servidor deve executar `php artisan schedule:run` a cada minuto para habilitar a atualização automática.

## Desenvolvimento

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan test
```
