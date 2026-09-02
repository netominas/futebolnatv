<?php

namespace App\Console\Commands;

use App\Services\WostiEventSynchronizer;
use Illuminate\Console\Command;

class SyncWostiEventsCommand extends Command
{
    protected $signature = 'wosti:sync-events';

    protected $description = 'Sincroniza somente jogos com transmissão informados pela Wosti Brasil';

    public function handle(WostiEventSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync();

        $this->info("Wosti: {$result['received']} recebidos, {$result['imported']} importados, {$result['skipped']} ignorados e {$result['channels']} canais vinculados.");

        return self::SUCCESS;
    }
}
