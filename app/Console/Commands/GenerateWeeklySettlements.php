<?php
// File: app/Console/Commands/GenerateWeeklySettlements.php
// Deskripsi: Command untuk generate tagihan settlement setiap Senin

namespace App\Console\Commands;

use App\Services\SettlementService;
use Illuminate\Console\Command;

class GenerateWeeklySettlements extends Command
{
    protected $signature = 'gomad:generate-settlements';
    protected $description = 'Generate tagihan settlement mingguan untuk semua warung setiap Senin';

    public function __construct(
        private readonly SettlementService $settlementService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generating weekly settlements...');

        // Mark overdue settlements
        $this->settlementService->markOverdueSettlements();

        // Generate new settlements
        $this->settlementService->generateWeeklySettlements();

        $this->info('Weekly settlements generation completed.');

        return Command::SUCCESS;
    }
}

// End of file