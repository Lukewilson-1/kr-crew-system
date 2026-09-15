<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendDepartureSms extends Command
{
    protected $signature = 'crew:send-departure-sms {serviceId} {depot?}';

    protected $description = 'Send a short SMS departure reminder to crew booked for a train service';

    public function handle(): int
    {
        $serviceId = trim((string) $this->argument('serviceId'));
        $depot = trim((string) ($this->argument('depot') ?? ''));

        if (! Schema::hasTable('train_services') || ! Schema::hasTable('crew_records')) {
            $this->warn('train_services / crew_records are not present; nothing to do.');

            return self::SUCCESS;
        }

        $svc = DB::table('train_services')->where('service_id', $serviceId)->first();
        if (! $svc) {
            $this->error("No train service found with service id '{$serviceId}'.");

            return self::FAILURE;
        }

        if ($depot === '') {
            $depot = (string) $svc->depot_code;
        }

        $depotNeedle = mb_strtolower($depot);
        $departureTime = (string) $svc->departure_time;
        $sms = app(SmsService::class);

        $sent = 0;
        $withoutMobile = 0;

        foreach (DB::table('crew_records')->orderBy('record_id')->get() as $row) {
            $payload = json_decode((string) ($row->payload ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }

            if (mb_strtolower(trim((string) ($payload['depot'] ?? $row->depot ?? ''))) !== $depotNeedle) {
                continue;
            }

            $mobile = $this->crewMobile($row, $payload);
            if ($mobile === '') {
                $withoutMobile++;

                continue;
            }

            $sms->sendDepartureReminder($mobile, $serviceId, $depot, $departureTime);
            $sent++;
        }

        $this->info("Departure SMS for {$serviceId} @ {$depot}: {$sent} SMS sent".($withoutMobile > 0 ? ", {$withoutMobile} skipped (no mobile on record)" : '').'.');

        return self::SUCCESS;
    }

    protected function crewMobile(object $row, array $payload): string
    {
        $contact = $payload['contact'] ?? null;
        $mobile = is_array($contact) ? trim((string) ($contact['mobile'] ?? '')) : '';
        if ($mobile === '') {
            $mobile = trim((string) ($payload['mobile'] ?? ''));
        }
        if ($mobile === '') {
            $mobile = trim((string) ($payload['phone'] ?? ''));
        }

        return $mobile;
    }
}