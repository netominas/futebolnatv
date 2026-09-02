<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncWostiLogosCommand extends Command
{
    protected $signature = 'wosti:sync-logos {--force : Baixa novamente os arquivos existentes}';

    protected $description = 'Baixa e armazena localmente as logos de ligas e times da Wosti';

    public function handle(): int
    {
        $result = ['downloaded' => 0, 'existing' => 0, 'failed' => 0];

        $this->syncModels(Competition::query(), 'competitions', $result);
        $this->syncModels(Team::query(), 'teams', $result);

        $this->info("Logos Wosti: {$result['downloaded']} baixadas, {$result['existing']} existentes e {$result['failed']} falhas.");

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array{downloaded:int, existing:int, failed:int} $result */
    private function syncModels(Builder $query, string $directory, array &$result): void
    {
        $query->whereNotNull('image')
            ->orderBy('id')
            ->chunkById(100, function ($models) use ($directory, &$result): void {
                foreach ($models as $model) {
                    $this->syncLogo($model, $directory, $result);
                }
            });
    }

    /** @param array{downloaded:int, existing:int, failed:int} $result */
    private function syncLogo(Model $model, string $directory, array &$result): void
    {
        $path = "wosti/{$directory}/{$model->wosti_id}.png";

        if (! $this->option('force') && Storage::disk('public')->exists($path)) {
            $model->update(['local_logo_path' => $path]);
            $result['existing']++;

            return;
        }

        try {
            $filename = basename((string) $model->image);
            $url = rtrim((string) config('services.wosti.logo_base_url'), '/').'/'.rawurlencode($filename);
            $response = Http::timeout(15)->retry(2, 500)->get($url);
            $body = $response->body();

            if (! $response->successful()
                || ! str_starts_with((string) $response->header('Content-Type'), 'image/png')
                || strlen($body) > 2_000_000
                || @getimagesizefromstring($body) === false) {
                $result['failed']++;
                $this->warn("Logo inválida ou indisponível: {$model->name}");

                return;
            }

            Storage::disk('public')->put($path, $body);
            $model->update(['local_logo_path' => $path]);
            $result['downloaded']++;
        } catch (Throwable $exception) {
            report($exception);
            $result['failed']++;
            $this->warn("Falha ao baixar a logo: {$model->name}");
        }
    }
}
