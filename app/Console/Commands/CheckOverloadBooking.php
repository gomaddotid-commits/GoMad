<?php
// File: app/Console/Commands/CheckOverloadBooking.php
// Deskripsi: Command untuk mengecek jadwal dengan okupansi >= 80%

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\OverloadService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckOverloadBooking extends Command
{
    protected $signature = 'gomad:check-overload';
    protected $description = 'Cek jadwal dengan okupansi >= 80% dan kirim notifikasi warning ke agency';

    public function __construct(
        private readonly OverloadService $overloadService,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking schedules for overload warning...');

        $schedules = Schedule::with(['agency.user', 'route', 'vehicle'])
            ->where('is_active', true)
            ->where('departure_date', '>=', now()->toDateString())
            ->where('departure_date', '<=', now()->addDays(3)->toDateString())
            ->get();

        $warningCount = 0;

        foreach ($schedules as $schedule) {
            $occupancyRate = $this->overloadService->getOccupancyRate($schedule);
            $warningLevel = $this->overloadService->getWarningLevel($schedule);

            if (in_array($warningLevel, ['warning', 'full'])) {
                $this->notificationService->overloadWarning($schedule);
                $warningCount++;

                $this->info("Warning sent for schedule #{$schedule->id} - {$schedule->route->route_name} - Occupancy: {$occupancyRate}%");
            }
        }

        $this->info("Total warnings sent: {$warningCount}");

        return Command::SUCCESS;
    }
}

// End of file