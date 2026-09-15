<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateWeeklyTrainBookings extends Command
{
    protected $signature = 'crew:generate-weekly-train-bookings';

    protected $description = 'Read active train_services weekly patterns and write next-week pendingBooking payloads (7-day advance)';

    public function handle(): int
    {
        if (! Schema::hasTable('train_services') || ! Schema::hasTable('crew_records')) {
            $this->warn('train_services / crew_records are not present; nothing to generate.');

            return self::SUCCESS;
        }

        $services = DB::table('train_services')
            ->where('is_active', true)
            ->get();

        if ($services->isEmpty()) {
            $this->info('No active train_services to generate.');

            return self::SUCCESS;
        }

        $records = DB::table('crew_records')->get();

        // Next week, Monday..Sunday for the 7-day advance window. Option A
        // guard (PromotePendingBookings::parseDepartureTime) anchors on the
        // explicit departureDate we write, so a staged booking cannot promote
        // a week early.
        $start = now()->startOfDay()->addDays(7);
        $end = $start->copy()->addDays(6);

        $written = 0;

        foreach ($services as $svc) {
            $day = (int) $svc->departure_day;
            $time = (string) $svc->departure_time;
            $depot = mb_strtolower(trim((string) $svc->depot_code));

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                if ((int) $cursor->format('N') !== $day) {
                    continue;
                }

                $date = $cursor->toDateString();
                $matched = 0;

                foreach ($records as $row) {
                    $payload = json_decode((string) ($row->payload ?? '{}'), true);
                    if (! is_array($payload)) {
                        $payload = [];
                    }

                    if (mb_strtolower(trim((string) ($payload['depot'] ?? $row->depot ?? ''))) !== $depot) {
                        continue;
                    }

                    $pb = $payload['pendingBooking'] ?? null;
                    if (is_array($pb)
                        && ($pb['serviceId'] ?? null) === $svc->service_id
                        && ($pb['departureDate'] ?? null) === $date) {
                        continue; // already staged for this exact departure
                    }

                    $payload['pendingBooking'] = [
                        'serviceId' => (string) $svc->service_id,
                        'departureDate' => $date,
                        'departureTime' => $time,
                        'trainType' => (string) ($svc->train_type ?? 'commuter'),
                        'route' => (string) ($svc->route_code ?? $svc->depot_code ?? ''),
                    ];

                    DB::table('crew_records')->where('record_id', $row->record_id)->update([
                        'payload' => json_encode($payload),
                        'updated_at' => now(),
                    ]);

                    $written++;
                    $matched++;
                }

                if ($matched > 0) {
                    $this->info(sprintf('  -> %s %s (%s): %d crew booking(s) staged', $svc->service_id, $date, $time, $matched));
                }
            }
        }

        $this->info("Weekly train bookings: {$written} pendingBooking(s) staged for next week.");

        return self::SUCCESS;
    }
}