<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\SystemNotification;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutoCheckoutExpiredRest extends Command
{
    protected $signature = 'running-rooms:auto-checkout-rested';

    protected $description = 'Auto check out crew whose running-room rest period has ended and notify HQ + booking officers';

    /**
     * Matches the frontend rule in public/js/helpers.js getRestHours():
     * resting away from the home depot = 10h, at the home depot = 12h.
     */
    protected function restHours(array $payload): int
    {
        $away = trim((string) ($payload['awayDepot'] ?? ''));
        $depot = trim((string) ($payload['depot'] ?? ''));

        return $away !== '' && $depot !== '' && strcasecmp($away, $depot) !== 0 ? 10 : 12;
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

    protected function crewPayloadForStaffNo(string $staffNo): ?array
    {
        if (! Schema::hasTable('crew_members') || ! Schema::hasTable('crew_records')) {
            return null;
        }

        $member = DB::table('crew_members')->where('staff_number', trim($staffNo))->first();
        if (! $member || ! ($member->record_id ?? null)) {
            return null;
        }

        $row = DB::table('crew_records')->where('record_id', $member->record_id)->first();
        if (! $row) {
            return null;
        }

        $payload = json_decode($row->payload ?? '{}', true);

        return is_array($payload) ? $payload : null;
    }

    public function handle(): int
    {
        if (! Schema::hasTable('attendance_records') || ! Schema::hasTable('system_notifications')) {
            return self::SUCCESS;
        }

        $now = now();
        $checkedOut = 0;

        AttendanceRecord::query()
            ->where('status', 'in')
            ->with('room')
            ->orderBy('arrival_date')
            ->orderBy('arrival_time')
            ->each(function (AttendanceRecord $record) use (&$checkedOut, $now) {
                $payload = $this->crewPayloadForStaffNo($record->staff_no);
                if (! is_array($payload)) {
                    return;
                }

                if (trim((string) ($payload['status'] ?? '')) !== 'R') {
                    return;
                }

                if (! empty($payload['rrCheckedOut'])) {
                    return;
                }

                $restStarted = $payload['restStarted'] ?? null;
                if (! $restStarted) {
                    return;
                }

                try {
                    $started = new \DateTimeImmutable((string) $restStarted);
                } catch (\Throwable) {
                    return;
                }

                $expiresAt = $started->modify(sprintf('+%d hours', $this->restHours($payload)));
                if ($now->lt($expiresAt)) {
                    return;
                }

                $departureDate = $now->toDateString();
                $departureTime = $now->format('H:i');

                $record->checkOut($departureDate, $departureTime);

                $this->syncCrewFromCheckOut($record, $departureDate, $departureTime);
                $this->notifyRestEnded($record, $payload, $departureDate, $departureTime);

                $checkedOut++;
            });

        $this->info("Auto-checked out {$checkedOut} crew member(s) with an ended rest period.");

        return self::SUCCESS;
    }

    protected function syncCrewFromCheckOut(AttendanceRecord $record, string $departureDate, string $departureTime): void
    {
        if (! Schema::hasTable('crew_members') || ! Schema::hasTable('crew_records')) {
            return;
        }

        $member = DB::table('crew_members')->where('staff_number', trim($record->staff_no))->first();
        if (! $member || ! ($member->record_id ?? null)) {
            return;
        }

        $row = DB::table('crew_records')->where('record_id', $member->record_id)->first();
        if (! $row) {
            return;
        }

        $payload = json_decode($row->payload ?? '{}', true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $wasResting = trim((string) ($payload['status'] ?? '')) === 'R';

        $payload = array_merge($payload, [
            'rrCheckedOut' => true,
            'rrCheckedOutAt' => now()->toIso8601String(),
            'lastUpdated' => now()->toIso8601String(),
            'updatedBy' => 'running-room-auto',
        ]);

        if ($wasResting) {
            $payload['status'] = 'SB';
            $payload['since'] = $departureTime;
            $payload['restStarted'] = null;
            $payload['awayDepot'] = null;
        }

        DB::table('crew_records')->where('record_id', $member->record_id)->update([
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);
    }

    protected function notifyRestEnded(AttendanceRecord $record, array $payload, string $departureDate, string $departureTime): void
    {
        $crewName = trim((string) ($record->name ?? $payload['name'] ?? ''));
        $crewDepot = trim((string) ($payload['depot'] ?? ''));
        $roomName = trim((string) ($record->room?->name ?? $payload['rrRoomName'] ?? ''));
        $roomDepot = trim((string) ($record->room?->depot_code ?? $payload['rrRoomDepot'] ?? ''));
        $hours = $this->restHours($payload);

        $title = 'Rest period complete — crew auto-checked out';
        $body = sprintf(
            '%s (%s) has completed their %dh rest at %s and was automatically checked out at %s %s.',
            $crewName ?: $record->staff_no,
            $record->staff_no,
            $hours,
            $roomName ?: 'the running room',
            $departureDate,
            $departureTime
        );

        $recipients = array_values(array_unique(array_merge(
            $this->hqAdminUsernames(),
            $this->bookingOfficerUsernamesForDepot($crewDepot),
            $this->bookingOfficerUsernamesForDepot($roomDepot)
        )));

        $data = [
            'staff_no' => $record->staff_no,
            'crew_name' => $crewName,
            'room_id' => $record->room_id,
            'room_name' => $roomName,
            'room_depot' => $roomDepot,
            'checked_out_at' => $departureDate.' '.$departureTime,
            'type' => 'running_room_auto_checkout',
        ];

        $now = now();
        foreach ($recipients as $username) {
            SystemNotification::create([
                'recipient_username' => $username,
                'type' => 'running_room_auto_checkout',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'read_at' => null,
            ]);
        }

        $this->info(sprintf('  -> checked out %s from %s; notified %d user(s)', $record->staff_no, $roomName ?: '?', count($recipients)));
    }
}
