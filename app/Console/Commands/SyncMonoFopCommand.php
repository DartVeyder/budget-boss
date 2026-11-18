<?php

namespace App\Console\Commands;

use App\Models\Fop;
use App\Services\Finance\Fop\FopMonoSyncService;
use Illuminate\Console\Command;

class SyncMonoFopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mono:sync-fop {--fop= : Specific FOP ID to sync} {--days=30 : Days lookback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize FOP transactions from Monobank API';

    /**
     * Execute the console command.
     */
    public function handle(FopMonoSyncService $syncService): int
    {
        $fopId = $this->option('fop');
        $days = (int)$this->option('days');

        $query = Fop::where('is_active', true);
        if ($fopId) {
            $query->where('id', $fopId);
        }

        $fops = $query->get();

        if ($fops->isEmpty()) {
            $this->warn('Не знайдено активних профілів ФОП для синхронізації.');
            return self::SUCCESS;
        }

        $from = now()->subDays($days)->startOfDay();
        $to = now();

        $this->info("Початок синхронізації ФОП рахунків з Monobank (період: {$from->toDateString()} - {$to->toDateString()})...");

        foreach ($fops as $fop) {
            $this->line("Синхронізація ФОП #{$fop->id}: {$fop->name} (IBAN: {$fop->iban})...");

            $res = $syncService->sync($fop, $from, $to);

            if ($res['success']) {
                $this->info("  [OK] {$res['message']}");
            } else {
                $this->error("  [FAIL] {$res['message']}");
            }
        }

        $this->info('Синхронізацію завершено.');
        return self::SUCCESS;
    }
}

