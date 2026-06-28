<?php
// File: app/Console/Commands/SendScheduleReminder.php
// Deskripsi: Command untuk mengirim pengingat jadwal H-1 jam 07:00

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduleReminder extends Command
{
    protected $signature = 'gomad:send-reminder';
    protected $description = 'Kirim pengingat jadwal H-1 kepada customer dan driver';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending schedule reminders...');

        $tomorrow = Carbon::tomorrow()->toDateString();

        $schedules = Schedule::with([
            'bookings' => function ($query) {
                $query->whereIn('status', ['paid', 'confirmed'])
                    ->with('customer');
            },
            'driver',
            'route',
            'vehicle',
        ])
        ->where('departure_date', $tomorrow)
        ->where('is_active', true)
        ->get();

        $reminderCount = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->bookings->isNotEmpty() || $schedule->driver) {
                $this->notificationService->scheduleReminder($schedule);
                $reminderCount++;

                $this->info("Reminder sent for schedule #{$schedule->id} - {$schedule->route->route_name}");
            }
        }

        $this->info("Total reminders sent: {$reminderCount}");

        return Command::SUCCESS;
    }
}

// End of file