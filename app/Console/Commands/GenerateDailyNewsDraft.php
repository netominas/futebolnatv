<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DailyNewsDraftGenerator;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class GenerateDailyNewsDraft extends Command
{
    protected $signature = 'news:generate-daily-draft {date? : Data da programação no formato YYYY-MM-DD}';

    protected $description = 'Gera um rascunho editorial com base nos jogos televisionados do dia';

    public function handle(DailyNewsDraftGenerator $generator): int
    {
        $author = User::query()->oldest('id')->first();
        if (! $author) {
            $this->error('Nenhum usuário editorial foi encontrado.');

            return self::FAILURE;
        }

        $dateValue = $this->argument('date') ?: now('America/Sao_Paulo')->format('Y-m-d');
        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $dateValue, 'America/Sao_Paulo');
        } catch (InvalidFormatException) {
            $date = null;
        }

        if (! $date || $date->format('Y-m-d') !== $dateValue) {
            $this->error('Use uma data válida no formato YYYY-MM-DD.');

            return self::FAILURE;
        }

        try {
            $article = $generator->generate($date, $author);
        } catch (ValidationException $exception) {
            $this->warn(collect($exception->errors())->flatten()->first());

            return self::SUCCESS;
        }
        $this->info("Rascunho pronto: {$article->title}");

        return self::SUCCESS;
    }
}
