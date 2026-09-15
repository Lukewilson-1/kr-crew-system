<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mirrors the crew app's scheduled-booking rules (public/js/app.js):
 *  - one hour before a scheduled departure, notify officers/HQ, the room
 *    attendants (with a check-out action) and email the crew member;
 *  - at departure, promote the booking to Booked (status BK) so the crew's
 *    actual status changes exactly when the train leaves.
 *
 * Runs alongside running-rooms:auto-checkout-rested via the cron webhook.
 */
class PromotePendingBookings extends Command
{
    protected $signature = 'crew:promote-pending-bookings';

    protected $description = 'Send T-1hr notices for scheduled crew bookings and auto-promote them to Booked at departure';

    public function handle(): int
    {
        if (! Schema::hasTable('crew_records') || ! Schema::hasTable('crew_members')) {
            return self::SUCCESS;
        }

        $now = now();
        $promoted = 0;
        $notified = 0;

        foreach (DB::table('crew_records')->orderBy('record_id')->get() as $row) {
            $payload = json_decode((string) ($row->payload ?? '{}'), true);
            if (! is_array($payload) || ! isset($payload['pendingBooking']) || ! is_array($payload['pendingBooking'])) {
                continue;
            }

            $pb = $payload['pendingBooking'];
            $depart = $this->parseDepartureTime(
                (string) ($pb['departureTime'] ?? ''),
                array_key_exists('departureDate', $pb) ? (string) ($pb['departureDate'] ?? '') : null,
            );
            if (! $depart) {
                continue;
            }

            $oneHourBefore = $depart->modify('-1 hour');

            if ($now->gte($oneHourBefore) && $now->lt($depart) && empty($pb['notificationSentAt'])) {
                $this->notifyDepartureApproaching($row, $payload, $pb, $depart);

                $payload['pendingBooking']['notificationSentAt'] = $now->toIso8601String();
                $this->savePayload($row->record_id, $payload);
                $notified++;

                continue;
            }

            if ($now->gte($depart)) {
                $this->promote($row, $payload, $pb, $depart);
                $promoted++;
            }
        }

        $this->info("Scheduled crew bookings: {$promoted} promoted to Booked, {$notified} T-1hr notification(s) sent.");

        return self::SUCCESS;
    }

    protected function parseDepartureTime(string $time, ?string $departureDate = null): ?\DateTimeImmutable
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $m)) {
            return null;
        }

        // Option A: honor an explicit departureDate (YYYY-MM-DD) written by the
        // weekly generator so a next-week booking is NOT promoted today. Legacy
        // time-only payloads fall back to anchoring on today, preserving the
        // Phase 1 / app.js behaviour exactly.
        if (is_string($departureDate) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($departureDate), $d)
            && checkdate((int) $d[2], (int) $d[3], (int) $d[1])) {
            $anchor = now()->setDate((int) $d[1], (int) $d[2], (int) $d[3]);
        } else {
            $anchor = now();
        }

        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            sprintf(
                '%04d-%02d-%02d %02d:%02d',
                $anchor->year,
                $anchor->month,
                $anchor->day,
                (int) $m[1],
                (int) $m[2],
            ),
            $anchor->getTimezone(),
        ) ?: null;
    }

    protected function promote(object $row, array $payload, array $pb, \DateTimeImmutable $depart): void
    {
        $now = now();
        $day = (int) $depart->format('j');
        $departureTime = (string) ($pb['departureTime'] ?? $depart->format('H:i'));
        $baseNote = 'Booked at '.$departureTime.' (scheduled)';

        $monthly = $payload['monthly'] ?? [];
        if (! is_array($monthly)) {
            $monthly = [];
        }
        $monthly['d'.$day] = 'BK';

        $payload = array_merge($payload, [
            'status' => 'BK',
            'since' => $departureTime,
            'bookTime' => $departureTime,
            'trainType' => (string) ($pb['trainType'] ?? $payload['trainType'] ?? ''),
            'route' => (string) ($pb['route'] ?? $payload['route'] ?? ''),
            'restStarted' => null,
            'awayDepot' => null,
            'monthly' => $monthly,
            'status_segments' => $this->buildSegmentsForPromotion($payload, $day, $pb, $depart),
            'notes' => trim((string) ($payload['notes'] ?? '')) === ''
                ? $baseNote
                : trim((string) ($payload['notes'] ?? '')).'; '.$baseNote,
            'lastUpdated' => $now->toIso8601String(),
            'updatedBy' => 'Auto-system',
        ]);
        unset($payload['pendingBooking']);

        $this->savePayload($row->record_id, $payload);
        $this->syncCrewStatusSegment($row, $payload, $day, $pb, $depart);

        $name = trim((string) ($payload['name'] ?? $row->crew_id ?? ''));
        $this->info(sprintf('  -> promoted %s to Booked at %s', $name !== '' ? $name.' ('.$row->record_id.')' : $row->record_id, $departureTime));
    }

    /**
     * Append a Booked segment starting at the departure time, mirroring the
     * frontend buildStatusSegmentsForDay() so the crew day timeline stays
     * contiguous (the previous segment is clamped to end at the departure).
     */
    protected function buildSegmentsForPromotion(array $payload, int $day, array $pb, \DateTimeImmutable $depart): array
    {
        $segments = $payload['status_segments'] ?? [];
        if (! is_array($segments)) {
            $segments = [];
        }

        $daySegs = [];
        $otherSegs = [];
        foreach ($segments as $seg) {
            if (is_array($seg) && (int) ($seg['day'] ?? 0) === $day) {
                $daySegs[] = $seg;
            } else {
                $otherSegs[] = $seg;
            }
        }

        $startMin = ((int) $depart->format('G')) * 60 + (int) $depart->format('i');
        $startMin = max(0, min(1439, $startMin));
        $departureTime = (string) ($pb['departureTime'] ?? $depart->format('H:i'));

        $maxOrder = 100;
        foreach ($daySegs as $seg) {
            $maxOrder = max($maxOrder, (int) ($seg['sort_order'] ?? 100));
        }

        $newSeg = [
            'day' => $day,
            'sort_order' => $maxOrder + 100,
            'status_code' => 'BK',
            'status' => 'BK',
            'start_time' => $this->minutesToTime($startMin),
            'end_time' => '23:59',
            'note' => 'Booked at '.$departureTime.' (scheduled)',
        ];

        if (count($daySegs)) {
            $last = $daySegs[count($daySegs) - 1];
            $prevEnd = trim((string) ($last['end_time'] ?? '')) === '23:59'
                ? 1440
                : $this->timeToMinutes((string) ($last['end_time'] ?? ''));
            if ($prevEnd > $startMin) {
                $last['end_time'] = $startMin <= 0 ? '00:00' : $this->minutesToTime($startMin);
            }
        }

        return array_merge($otherSegs, $daySegs, [$newSeg]);
    }

    protected function syncCrewStatusSegment(object $row, array $payload, int $day, array $pb, \DateTimeImmutable $depart): void
    {
        if (! Schema::hasTable('crew_status_segments')) {
            return;
        }

        $monthKey = $depart->format('Y-m');
        if ($monthKey !== now()->format('Y-m')) {
            return;
        }

        $maxOrder = (int) DB::table('crew_status_segments')
            ->where('crew_record_id', $row->record_id)
            ->where('month_key', $monthKey)
            ->where('day', $day)
            ->max('sort_order');

        DB::table('crew_status_segments')->updateOrInsert(
            ['segment_id' => (string) Str::uuid()],
            [
                'crew_record_id' => $row->record_id,
                'crew_id' => trim((string) ($payload['id'] ?? $row->crew_id ?? $row->record_id)),
                'depot_code' => trim((string) ($payload['depot'] ?? $row->depot ?? '')),
                'month_key' => $monthKey,
                'day' => $day,
                'sort_order' => $maxOrder + 100,
                'status_code' => 'BK',
                'train_type' => $pb['trainType'] ?? null,
                'route' => $pb['route'] ?? null,
                'book_time' => $pb['departureTime'] ?? null,
                'start_time' => $depart->format('H:i'),
                'end_time' => '23:59',
                'notes' => 'Booked at '.($pb['departureTime'] ?? '').' (scheduled)',
                'metadata' => json_encode(['source' => 'scheduled-booking-cron']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    protected function notifyDepartureApproaching(object $row, array $payload, array $pb, \DateTimeImmutable $depart): void
    {
        $crewName = trim((string) ($payload['name'] ?? $row->crew_id ?? 'Crew member'));
        $staffNo = trim((string) ($row->staff_number ?? $payload['staff_number'] ?? ''));
        $depot = trim((string) ($payload['depot'] ?? $row->depot ?? ''));
        $departureTime = (string) ($pb['departureTime'] ?? $depart->format('H:i'));
        $trainType = (string) ($pb['trainType'] ?? '');

        $title = 'Scheduled booking departs in one hour';
        $body = sprintf(
            '%s%s departs at %s%s in about one hour.',
            $crewName ?: 'Crew member',
            $staffNo !== '' ? ' ('.$staffNo.')' : ' ('.$row->record_id.')',
            $departureTime,
            $trainType !== '' ? ' ('.$trainType.')' : '',
        );

        $data = [
            'staff_no' => $staffNo,
            'crew_name' => $crewName,
            'departure_time' => $departureTime,
            'train_type' => $trainType,
            'route' => $pb['route'] ?? null,
        ];

        $svc = app(\App\Services\NotificationService::class);

        $recipients = array_values(array_unique(array_merge(
            $this->hqAdminUsernames(),
            $this->bookingOfficerUsernamesForDepot($depot),
        )));

        $attendanceId = null;
        $roomName = null;
        if (Schema::hasTable('attendance_records') && $staffNo !== '') {
            $attendance = DB::table('attendance_records')
                ->where('staff_no', $staffNo)
                ->where('status', 'in')
                ->orderByDesc('arrival_date')
                ->orderByDesc('arrival_time')
                ->first();

            if ($attendance) {
                $attendanceId = $attendance->id;
                $roomName = null;
                if (Schema::hasTable('rooms') && $attendance->room_id) {
                    $roomName = (string) DB::table('rooms')->where('room_id', $attendance->room_id)->value('name');
                }

                if ($attendance->room_id) {
                    $attendants = User::query()
                        ->where('is_active', true)
                        ->where('role_code', 'attendant')
                        ->where('room_id', $attendance->room_id)
                        ->pluck('username')
                        ->all();
                    $recipients = array_values(array_unique(array_merge($recipients, $attendants)));
                }

                $data['action'] = 'checkout';
                $data['attendance_id'] = $attendanceId;
                $data['room_name'] = $roomName ?: null;
            }
        }

        $svc->notifyUsers($recipients, $title, $body, 'crew_booking_1hr', $data);

        $crewEmail = trim((string) DB::table('crew_members')->where('record_id', $row->record_id)->value('email'));
        if ($crewEmail !== '') {
            $svc->email($crewEmail, $title, $body, 'crew_booking_1hr', $data);
        }

        $mobile = $this->crewMobile($row, $payload);
        if ($mobile !== '') {
            $serviceId = (string) ($pb['serviceId'] ?? '');
            if ($serviceId !== '') {
                app(\App\Services\SmsService::class)->sendDepartureReminder($mobile, $serviceId, $depot, $departureTime);
            }
        } else {
            // No mobile on record: fall through silently — the email above has
            // already covered the alert, so the existing email path is unbroken.
            $this->info(sprintf('  -> no mobile on record for %s (%s); SMS skipped', $crewName ?: $row->record_id, $staffNo ?: '?'));
        }

        $this->info(sprintf('  -> T-1hr notice for %s (%s)', $crewName ?: $row->record_id, $staffNo ?: '?'));
    }

    protected function savePayload(string $recordId, array $payload): void
    {
        DB::table('crew_records')->where('record_id', $recordId)->update([
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);
    }

    /**
     * Crew mobile number for SMS reminders. Primary source is
     * crew_records.payload.contact.mobile (register spec); falls back to the
     * top-level payload mobile/phone keys if present. Blank when unset so the
     * caller can skip gracefully.
     */
    protected function crewMobile(object $row, array $payload): string
    {
        $contact = $payload['contact'] ?? null;
        $mobile = is_array($contact) ? trim((string) ($contact['mobile'] ?? '')) : '';
        if ($mobile === '') {
            $mobile = trim((string) ($payload['mobile'] ?? ''));
        }
        if ($mobile === '') {
            $mobile = trim((string) DB::table('crew_members')->where('record_id', $row->record_id)->value('phone'));
        }

        return $mobile;
    }

    protected function resolveDepotIdentifiers(string $depot): array
    {
        $depot = trim($depot);
        if ($depot === '' || ! Schema::hasTable('depots')) {
            return $depot === '' ? [] : [mb_strtolower($depot)];
        }

        $row = DB::table('depots')
            ->whereRaw('LOWER(depot_code) = ?', [mb_strtolower($depot)])
            ->orWhereRaw('LOWER(depot_name) = ?', [mb_strtolower($depot)])
            ->first();

        $identifiers = [$depot];
        if ($row) {
            $identifiers[] = $row->depot_code;
            $identifiers[] = $row->depot_name;
        }

        return array_values(array_unique(array_filter(array_map(fn ($value) => mb_strtolower((string) $value), $identifiers))));
    }

    protected function bookingOfficerUsernamesForDepot(string $depot): array
    {
        $identifiers = $this->resolveDepotIdentifiers($depot);
        if (empty($identifiers)) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->where('role_code', 'booking_officer')
            ->where(function ($query) use ($identifiers) {
                foreach ($identifiers as $id) {
                    $query->orWhereRaw('LOWER(depot_code) = ?', [$id]);
                }
            })
            ->pluck('username')
            ->all();
    }

    protected function hqAdminUsernames(): array
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('is_hq', true)
                    ->orWhere('is_super_admin', true)
                    ->orWhere('role_code', 'hq_admin')
                    ->orWhereRaw('LOWER(depot_code) = ?', ['hq']);
            })
            ->pluck('username')
            ->all();
    }

    protected function timeToMinutes(?string $value): int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', trim((string) $value), $m)) {
            return 0;
        }

        return max(0, min(1439, (int) $m[1] * 60 + (int) $m[2]));
    }

    protected function minutesToTime(int $minutes): string
    {
        $safe = max(0, min(1439, $minutes));

        return sprintf('%02d:%02d', intdiv($safe, 60), $safe % 60);
    }
}