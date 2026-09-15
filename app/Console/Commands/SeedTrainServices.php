<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the train_services table with recurring weekly commuter/passenger
 * service patterns (Phase 2). Idempotent: upserts by the service_id primary
 * key, so re-running on the dev database is safe.
 */
class SeedTrainServices extends Command
{
    protected $signature = 'train-services:seed';

    protected $description = 'Seed recurring weekly commuter/passenger train_services patterns';

    public function handle(): int
    {
        if (! Schema::hasTable('train_services')) {
            $this->error('train_services table does not exist — run the migration first.');

            return self::FAILURE;
        }

        // [service_id, depot_code, route_code, train_type, departure_day, departure_time]
        $services = [
            ['PT-01', 'KBX01', 'KBX01', 'commuter', 1, '06:30'],
            ['PT-02', 'KBX01', 'KBX02', 'commuter', 5, '17:45'],
            ['PT-03', 'KBX02', 'KBX02', 'commuter', 1, '06:15'],
            ['PT-04', 'KBX02', 'KBX01', 'commuter', 5, '18:00'],
            ['PS-11', 'KBX01', 'KBX01', 'passenger', 1, '08:00'],
            ['PS-12', 'KBX02', 'KBX02', 'passenger', 6, '08:30'],
            ['PS-13', 'KBX01', 'KBX02', 'passenger', 7, '09:30'],
        ];

        $now = now();
        $upserted = 0;

        foreach ($services as $svc) {
            [$serviceId, $depotCode, $routeCode, $trainType, $day, $time] = $svc;

            DB::table('train_services')->updateOrInsert(
                ['service_id' => $serviceId],
                [
                    'depot_code' => $depotCode,
                    'route_code' => $routeCode,
                    'train_type' => $trainType,
                    'departure_day' => $day,
                    'departure_time' => $time,
                    'frequency_weeks' => 1,
                    'lead_days_advance' => 7,
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
            $upserted++;
        }

        $this->info("Seeded {$upserted} weekly train_services patterns.");

        return self::SUCCESS;
    }
}
