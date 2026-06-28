<?php
// File: app/Console/Kernel.php
// Deskripsi: Kernel untuk scheduling commands

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Check overload setiap jam
        $schedule->command('gomad:check-overload')->hourly();

        // Kirim pengingat jadwal H-1 setiap jam 07:00
        $schedule->command('gomad:send-reminder')->dailyAt('07:00');

        // Expire pembayaran pending setiap 5 menit
        $schedule->command('gomad:expire-payments')->everyFiveMinutes();

        // Generate settlement setiap Senin jam 00:01
        $schedule->command('gomad:generate-settlements')->weeklyOn(1, '00:01');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

// End of file