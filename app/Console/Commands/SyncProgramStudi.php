<?php

namespace App\Console\Commands;

use App\Services\UnwProgramStudiSyncService;
use Illuminate\Console\Command;

class SyncProgramStudi extends Command
{
    protected $signature = 'edom:sync-program-studi';

    protected $description = 'Sinkronisasi program studi lokal dari API panel-web UNW.';

    public function handle(UnwProgramStudiSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info('Sinkronisasi program studi berhasil.');
        $this->line('Baru: '.$result['created']);
        $this->line('Diperbarui: '.$result['updated']);
        $this->line('Dilewati: '.$result['skipped']);
        $this->line('Total API: '.$result['total']);

        return self::SUCCESS;
    }
}
