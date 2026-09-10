<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
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
            $this->info('Maintenance is not active.');

            return self::SUCCESS;
        }

        try {
            $data = app()->maintenanceMode()->data();
        } catch (\Throwable $e) {
            Log::warning('maintenance:auto-deactivate could not read maintenance data', ['error' => $e->getMessage()]);
            $this->error('Could not read maintenance data: '.$e->getMessage());

            return self::SUCCESS;
        }

        $endsAt = $data['ends_at'] ?? null;

        if (! is_string($endsAt) || $endsAt === '') {
            $this->info('No scheduled end time — leaving maintenance active (manual deactivation required).');

            return self::SUCCESS;
        }

        try {
            $endsAtTime = Carbon::parse($endsAt);
        } catch (\Throwable $e) {
            Log::warning('maintenance:auto-deactivate found an unparseable scheduled end', ['ends_at' => $endsAt, 'error' => $e->getMessage()]);
            $this->warn('Scheduled end is unparseable; leaving maintenance active.');

            return self::SUCCESS;
        }

        if (! $endsAtTime->isPast()) {
            $this->info('Scheduled end '.$endsAtTime->toIso8601String().' has not arrived yet — leaving maintenance active.');

            return self::SUCCESS;
        }

        app()->maintenanceMode()->deactivate();

        AuditLogger::record(
            event: 'maintenance_deactivated',
            entityType: 'maintenance_mode',
            entityId: 'maintenance',
            after: null,
            metadata: [
                'source' => 'scheduler',
                'scheduled_end' => $endsAt,
            ],
        );

        Log::info('maintenance:auto-deactivate took the site back online', ['scheduled_end' => $endsAt]);
        $this->info('Scheduled maintenance end reached — site is back online.');

        return self::SUCCESS;
    }
}