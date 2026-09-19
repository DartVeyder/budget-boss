<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Finance\Act\DocumentGenerationService;
use Illuminate\Console\Command;

class ImportActsFromTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acts:import-from-transactions {--user= : User ID to import for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically create Acts and Invoices for income transactions with attached files';

    /**
     * Execute the console command.
     */
    public function handle(DocumentGenerationService $docService): int
    {
        $userId = $this->option('user');
        $users = $userId ? User::where('id', $userId)->get() : User::all();

        $total = 0;
        foreach ($users as $user) {
            $this->info("Processing user #{$user->id} ({$user->name})...");
            $result = $docService->importFromTransactions($user->id);
            $imported = $result['imported'] ?? 0;
            $this->info("Imported {$imported} acts & invoices for user #{$user->id}.");
            $total += $imported;
        }

        $this->info("Done! Total imported: {$total}.");

        return Command::SUCCESS;
    }
}
