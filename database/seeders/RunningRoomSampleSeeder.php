<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Matter;
use App\Models\Room;
use App\Support\CrewLookup;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds rooms plus realistic sample occupancies and matters for the running
 * room register so daily / monthly / challenges reports have data to render.
 *
 * Occupancies are generated day by day, honouring each room's bed capacity,
 * using real crew members (staff number, name, designation) from the shared
 * crew_members table. Re-running skips non-empty register tables to avoid
 * wiping real data.
 */
class RunningRoomSampleSeeder extends Seeder
{
    protected array $rooms = [
        ['name' => 'Nairobi', 'beds' => 14],
        ['name' => 'Longonot', 'beds' => 4],
        ['name' => 'Mtito Andei', 'beds' => 24],
        ['name' => 'Nakuru', 'beds' => 26],
        ['name' => 'Nanyuki', 'beds' => 9],
        ['name' => 'Malaba', 'beds' => 14],
        ['name' => 'Eldoret', 'beds' => 18],
        ['name' => 'Kisumu', 'beds' => 12],
    ];

    /** staff_no => list of [start, end] Carbon ranges already booked, across ALL rooms. */
    protected array $bookings = [];

    public function run(): void
    {
        foreach ($this->rooms as $room) {
            Room::updateOrCreate(
                ['name' => $room['name']],
                [
                    'beds' => $room['beds'],
                    'password' => Hash::make(Str::lower(preg_replace('/[^a-z]/i', '', $room['name'])).'123'),
                ]
            );
        }

        if (AttendanceRecord::count() > 0 || Matter::count() > 0) {
            $this->command?->warn('Register tables already have data — skipping sample occupancies/matters.');

            return;
        }

        $crew = $this->eligibleCrew();
        $rooms = Room::query()->orderBy('name')->get(['id', 'name', 'beds']);

        mt_srand(20260813);

        foreach ($rooms as $room) {
            $this->seedOccupancy($room, $crew);
            $this->seedMatters($room);
        }

        $this->command?->info('Seeded '.AttendanceRecord::count().' attendance records and '.Matter::count().' matters.');
    }

    /** Advance through the crew list to the first member free for [start, end]. */
    protected function pickCrewMember(array $crew, int &$index, Carbon $start, Carbon $end): ?array
    {
        if (empty($crew)) {
            return null;
        }

        $attempts = 0;

        while ($attempts < count($crew)) {
            $candidate = $crew[$index % count($crew)];
            $index++;

            if ($this->crewIsFree($candidate['staff_no'], $start, $end)) {
                return $candidate;
            }

            $attempts++;
        }

        return null;
    }

    protected function crewIsFree(string $staffNo, Carbon $start, Carbon $end): bool
    {
        foreach ($this->bookings[$staffNo] ?? [] as [$bookedStart, $bookedEnd]) {
            if ($start->lte($bookedEnd) && $bookedStart->lte($end)) {
                return false;
            }
        }

        return true;
    }

    protected function eligibleCrew(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('crew_members')) {
            return [];
        }

        return DB::table('crew_members')
            ->where('is_active', 1)
            ->get()
            ->filter(fn ($member) => CrewLookup::isDesignationRestEligible($member->designation_code ?? ''))
            ->map(fn ($member) => [
                'staff_no' => (string) $member->staff_number,
                'name' => (string) ($member->display_name ?: trim(($member->first_name ?? '').' '.($member->last_name ?? ''))),
                'designation' => (string) ($member->designation_code ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function seedOccupancy(Room $room, array $crew): void
    {
        if (empty($crew)) {
            return;
        }

        $crewIndex = mt_rand(0, count($crew) - 1);
        $active = [];
        $days = 120;

        for ($offset = $days; $offset >= 0; $offset--) {
            $date = Carbon::today()->subDays($offset);

            // Finish sessions that ended yesterday.
            foreach ($active as $key => $session) {
                if ($session['end']->lt($date)) {
                    AttendanceRecord::query()
                        ->where('id', $session['record_id'])
                        ->update([
                            'status' => 'out',
                            'departure_date' => $session['end']->toDateString(),
                            'departure_time' => $this->randomTime(5, 8),
                        ]);
                    unset($active[$key]);
                }
            }
            $active = array_values($active);

            // Close enough to today: guarantee at least one occupant per room.
            $forceStart = $offset <= 3 && count($active) < min(2, $room->beds);
            $startChance = $forceStart ? 100 : 22;

            if (count($active) < $room->beds && mt_rand(0, 100) < $startChance) {
                $duration = $forceStart
                    ? $offset + 1 + mt_rand(0, 1)
                    : mt_rand(1, 3);
                $end = $date->copy()->addDays($duration);

                $member = $this->pickCrewMember($crew, $crewIndex, $date, $end);
                if (! $member) {
                    continue;
                }

                $record = AttendanceRecord::create([
                    'room_id' => $room->id,
                    'name' => $member['name'],
                    'staff_no' => $member['staff_no'],
                    'designation' => $member['designation'],
                    'bed_no' => 'B'.(count($active) + 1),
                    'arrival_date' => $date->toDateString(),
                    'arrival_time' => $this->randomTime(18, 23),
                    'remarks' => $this->randomRemark(),
                    'status' => 'in',
                ]);

                $this->bookings[$member['staff_no']][] = [$date->copy(), $end->copy()];
                $active[] = ['record_id' => $record->id, 'end' => $end];
            }
        }

        // Check out anything still active from the synthetic run (should only
        // be the "currently in" occupants that reach today).
        $now = Carbon::today();
        foreach ($active as $session) {
            if ($session['end']->lt($now)) {
                AttendanceRecord::query()
                    ->where('id', $session['record_id'])
                    ->update([
                        'status' => 'out',
                        'departure_date' => $session['end']->toDateString(),
                        'departure_time' => $this->randomTime(5, 8),
                    ]);
            }
        }
    }

    protected function seedMatters(Room $room): void
    {
        $categories = [
            'Maintenance', 'Cleanliness', 'Security',
            'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
        ];
        $descriptions = [
            'Broken bed frame reported on the upper berth.',
            'Light bulb out in the corridor; needs replacement.',
            'Bedding needs changing; sheets and blankets requested.',
            'Water pressure low in the washroom.',
            'Reported a leaking tap in the washroom.',
            'Cleaning required in the common area.',
            'Request for additional pillow and blanket.',
            'Fan not working in room.',
            'Door lock sticking; reported for attention.',
            'Pest issue in the room, needs fumigation.',
        ];

        $count = mt_rand(2, 4);
        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::today()->subDays(mt_rand(1, 110));
            $resolved = mt_rand(0, 100) < 55;

            Matter::create([
                'room_id' => $room->id,
                'date' => $date->toDateString(),
                'category' => $categories[mt_rand(0, count($categories) - 1)],
                'description' => $descriptions[mt_rand(0, count($descriptions) - 1)],
                'reported_by' => mt_rand(0, 1) ? 'Room Attendant' : 'Station Officer',
                'status' => $resolved ? 'resolved' : 'open',
                'resolved_date' => $resolved ? $date->copy()->addDays(mt_rand(1, 5))->toDateString() : null,
            ]);
        }
    }

    protected function randomTime(int $fromHour, int $toHour): string
    {
        return sprintf('%02d:%02d', mt_rand($fromHour, $toHour), mt_rand(0, 59));
    }

    protected function randomRemark(): ?string
    {
        $remarks = [
            null,
            'Night shift crew',
            'Overnight rest before morning duty',
            'Long-distance crew',
        ];

        return $remarks[mt_rand(0, count($remarks) - 1)];
    }
}
