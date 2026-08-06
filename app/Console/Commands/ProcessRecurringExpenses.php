<?php

namespace App\Console\Commands;

use App\Services\ExpenseService;
use Illuminate\Console\Command;

class ProcessRecurringExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and generate scheduled recurring expenses drafts';

    /**
     * Execute the console command.
     */
    public function handle(ExpenseService $expenseService)
    {
        $this->info('Starting recurring expense processing...');
        $generatedCount = $expenseService->generateScheduledRecurringExpenses();
        $this->info("Completed. Generated {$generatedCount} recurring expense entries.");
        return Command::SUCCESS;
    }
}
