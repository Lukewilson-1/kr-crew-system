<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AutoDeactivateMaintenance extends Command
{
    protected $signature = 'maintenance:auto-deactivate';

    protected $description = 'Automatically take the site back online when scheduled maintenance has passed its end time';

    public function handle(): int
    {
        if (! app()->maintenanceMode()->active()) {
            return self::SUCCESS;
        }

        try {
            $data = app()->maintenanceMode()->data();
        } catch (\Throwable $e) {
            Log::warning('maintenance:auto-deactivate could not read maintenance data', ['error' => $e->getMessage()]);

            return self::SUCCESS;
        }

        $endsAt = $data['ends_at'] ?? null;
        if (! is_string($endsAt) || $endsAt === '' || ! Carbon::parse($endsAt)->isPast()) {
            return self::SUCCESS;
        }

        app()->maintenanceMode()->deactivate();

        Log::info('maintenance:auto-deactivate took the site back online', ['scheduled_end' => $endsAt]);

        $this->info('Scheduled maintenance end reached — site is back online.');

        return self::SUCCESS;
    }
}
