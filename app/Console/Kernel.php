<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SeedDefaultReports::class,
        \App\Console\Commands\CopyEndOfDayStatus::class,
        \App\Console\Commands\AutoCheckoutExpiredRest::class,
        \App\Console\Commands\PruneAuditLogs::class,
        \App\Console\Commands\RotateBreakGlassCredentials::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        // Run the end-of-day status copy one minute after midnight server time.
        $schedule->command('crew:copy-end-of-day-status')->dailyAt('00:01');

        // Auto-check-out crew whose running-room rest period has ended and
        // notify HQ + the relevant booking officers. Runs every five minutes.
        $schedule->command('running-rooms:auto-checkout-rested')->everyFiveMinutes();

        // Bring the site back online automatically once scheduled maintenance
        // has passed its end time. Runs every minute.
        $schedule->command('maintenance:auto-deactivate')->everyMinute();

        // Enforce the audit log retention policy (minimum 12 months).
        $schedule->command('audit:prune --retention=365')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
