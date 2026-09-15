<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Matter;
use App\Models\MatterPhoto;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Support\CrewLookup;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RunningRoomController extends Controller
{
    public const DESIGNATIONS = ['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other'];

    public const CATEGORIES = [
        'Maintenance', 'Cleanliness', 'Security',
        'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
    ];

    protected static ?array $cachedCategories = null;

    protected function optionValue(string $key, array $defaults): array
    {
        if ($key === 'categories' && self::$cachedCategories !== null) {
            return self::$cachedCategories;
        }

        $value = Cache::remember("running_room_options:{$key}", 300, function () use ($key) {
            if (! Schema::hasTable('running_room_options')) {
                return null;
            }
            $row = DB::table('running_room_options')->where('key', $key)->first();

            return $row ? json_decode((string) $row->options, true) : null;
        });

        $resolved = is_array($value) && ! empty($value) ? array_values(array_map('strval', $value)) : $defaults;
        self::$cachedCategories = $resolved;

        return $resolved;
    }

    protected function designations(): array
    {
        // Designations are shared with the crew module: the register reads the
        // same list the crew app uses (designations table), so there is only one
        // list of crew designations across the whole system.
        if (Schema::hasTable('designations')) {
            $names = DB::table('designations')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('designation_name')
                ->pluck('designation_name')
                ->map(fn ($value) => (string) $value)
                ->all();

            $names = array_values(array_filter($names, fn ($value) => trim($value) !== ''));
            if (! empty($names)) {
                return $names;
            }
        }

        return static::DESIGNATIONS;
    }

    protected function categories(): array
    {
        return $this->optionValue('categories', static::CATEGORIES);
    }

    /**
     * Photo uploads for a matter are stored directly under public/matter-photos
     * (gitignored) so they are served by the web server without a symlink. Each
     * row in matter_photos stores the web-relative path.
     */
    protected function storeMatterPhotoFiles(Matter $matter, array $photos): void
    {
        $dir = public_path('matter-photos');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        foreach ($photos as $photo) {
            if (! $photo) {
                continue;
            }

            $name = Str::uuid()->toString().'.'.strtolower($photo->getClientOriginalExtension());
            $photo->move($dir, $name);

            $matter->photos()->create(['filename' => 'matter-photos/'.$name]);
        }
    }

    protected function deleteMatterPhotoFiles(Matter $matter): void
    {
        foreach ($matter->photos as $photo) {
            $this->deletePhotoFile($photo);
        }
    }

    protected function deletePhotoFile(MatterPhoto $photo): void
    {
        $path = public_path($photo->filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function index(Request $request)
    {
        if (! $request->user()) {
            return redirect('/login');
        }

        if (! $request->user()->canAccessRunningRooms()) {
            return redirect('/');
        }

        $tab = 'dashboard';
        $path = $request->path();
        if (str_ends_with($path, 'running-rooms/monthly')) {
            $tab = 'monthly';
        } elseif (str_ends_with($path, 'running-rooms/challenges')) {
            $tab = 'challenges';
        } elseif (str_ends_with($path, 'running-rooms/settings')) {
            $tab = 'settings';
        }

        return view('running_rooms.index', [
            'initialTab' => $tab,
            'initialData' => $this->buildPayload($request),
        ]);
    }

    /**
     * Standalone, print-ready "Matters Arising" report. Rendered as a fully
     * self-contained HTML page (styles + brand assets inline, evidence photos
     * embedded as base64 data URIs up to a size cap) so it can be printed to
     * PDF or downloaded as a single HTML file that works offline.
     */
    public function mattersReport(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        if (! $user->canAccessRunningRooms()) {
            return redirect('/');
        }

        $validator = Validator::make($request->query(), [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|in:open,resolved',
            'room' => 'nullable',
            'category' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            abort(422, 'Invalid report filters.');
        }

        $roomIds = $this->visibleRoomIds($user);

        // Multi-room selection: accepts ?room[]=1&room[]=2 (report options form)
        // or a single ?room=1 (SPA toolbar). No selection = all visible rooms.
        $rawRoom = $request->query('room');
        $requestedRoomIds = $rawRoom === null ? [] : (array) $rawRoom;
        $selectedRoomIds = array_values(array_filter(array_map('intval', $requestedRoomIds)));
        $selectedRoomIds = array_values(array_intersect($selectedRoomIds, $roomIds));

        $query = Matter::query()
            ->with('photos')
            ->whereIn('room_id', ! empty($selectedRoomIds) ? $selectedRoomIds : $roomIds);

        $status = $validator->validated()['status'] ?? null;
        if ($status) {
            $query->where('status', $status);
        }

        $category = $validator->validated()['category'] ?? null;
        if ($category) {
            $query->where('category', $category);
        }

        $from = $validator->validated()['from'] ?? null;
        $to = $validator->validated()['to'] ?? null;

        // Match the rolling window shown on screen when no explicit range is given.
        if ($from === null && $to === null) {
            $from = now()->subMonths(max(1, (int) config('running_rooms.history_months', 6)))->toDateString();
        }
        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }

        $matters = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $rooms = Room::query()
            ->whereIn('id', $roomIds)
            ->orderBy('name')
            ->get(['id', 'name', 'depot_code']);

        $roomNames = $rooms->pluck('name', 'id');

        $byRoom = [];
        $byCategory = [];
        foreach ($matters as $matter) {
            $name = $roomNames[$matter->room_id] ?? ('Room #'.$matter->room_id);
            $byRoom[$name] ??= ['total' => 0, 'open' => 0, 'resolved' => 0];
            $byRoom[$name]['total']++;
            $byRoom[$name][$matter->status === 'open' ? 'open' : 'resolved']++;
            $byCategory[$matter->category] = ($byCategory[$matter->category] ?? 0) + 1;
        }

        ksort($byRoom);
        uksort($byCategory, fn ($a, $b) => $byCategory[$b] <=> $byCategory[$a]);

        $categories = array_values(array_unique(array_filter(array_merge(
            $this->categories(),
            array_keys($byCategory),
            $category ? [$category] : [],
        ))));

        return view('running_rooms.matters_report', [
            'logoDataUri' => $this->reportLogoDataUri(),
            'rooms' => $rooms,
            'matters' => $matters->map(function (Matter $matter) use ($roomNames) {
                return [
                    'ticket_no' => $matter->ticket_no,
                    'date' => $matter->date?->format('d M Y'),
                    'room' => $roomNames[$matter->room_id] ?? ('Room #'.$matter->room_id),
                    'category' => $matter->category,
                    'status' => $matter->status,
                    'reported_by' => $matter->reported_by ?: '—',
                    'resolved_date' => $matter->resolved_date?->format('d M Y'),
                    'description' => $matter->description ? (string) $matter->description->toHtml() : '',
                    'photos' => $matter->photos->map(fn (MatterPhoto $photo) => $this->embeddedPhoto($photo))->values(),
                ];
            })->values(),
            'byRoom' => $byRoom,
            'byCategory' => $byCategory,
            'categories' => $categories,
            'scope' => [
                'roomIds' => $selectedRoomIds,
                'roomsAll' => $rooms->pluck('name')->all(),
                'roomsSelected' => collect($selectedRoomIds)
                    ->map(fn ($id) => $roomNames[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all(),
                'status' => $status,
                'category' => $category,
                'from' => $from,
                'to' => $to,
            ],
            'generatedBy' => $user->name ?: $user->username,
        ]);
    }

    /**
     * Embed a matter photo as a base64 data URI (bounded size) so downloaded and
     * printed reports carry the original evidence offline. Oversized or missing
     * files fall back to a plain web URL.
     */
    protected function embeddedPhoto(MatterPhoto $photo): array
    {
        $url = asset($photo->filename);
        $path = public_path($photo->filename);

        if (is_file($path)) {
            $maxBytes = max(64 * 1024, (int) config('running_rooms.report_embed_max_bytes', 2 * 1024 * 1024));
            $size = filesize($path);
            if ($size > 0 && $size <= $maxBytes) {
                $mime = function_exists('mime_content_type') ? (string) mime_content_type($path) : null;
                if ($mime && str_starts_with($mime, 'image/')) {
                    return [
                        'src' => 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path)),
                        'embedded' => true,
                        'url' => $url,
                    ];
                }
            }
        }

        return ['src' => $url, 'embedded' => false, 'url' => $url];
    }

    protected function reportLogoDataUri(): string
    {
        $logo = public_path('assets/logo.png');
        if (is_file($logo)) {
            $mime = function_exists('mime_content_type') ? (string) mime_content_type($logo) : 'image/png';
            return 'data:'.($mime ?: 'image/png').';base64,'.base64_encode((string) file_get_contents($logo));
        }

        return '';
    }

    /**
     * Rooms the user may manage. Attendants are tied to one room; depot users
     * (booking/station officers) may only manage the rooms in their own depot;
     * HQ/admin users see every room. A depot user still sees the rest status of
     * his own depot's crew anywhere else through the crew module.
     */
    protected function visibleRoomIds(User $user): array
    {
        if ($user->isAttendant()) {
            return [(int) $user->room_id];
        }

        $query = Room::query();
        if (! $user->isGlobalAccess()) {
            $depot = trim((string) $user->depot_code);
            if ($depot === '') {
                return [];
            }
            $query->where('depot_code', $depot);
        }

        return $query->pluck('id')->all();
    }

    protected function canManageRoom(User $user, mixed $roomId): bool
    {
        if ($user->isAttendant()) {
            return (int) $roomId === (int) $user->room_id;
        }

        if ($user->isGlobalAccess()) {
            return true;
        }

        $depot = trim((string) $user->depot_code);
        if ($depot === '') {
            return false;
        }

        $room = Room::query()->where('id', (int) $roomId)->first();

        return $room !== null && trim((string) $room->depot_code) === $depot;
    }

    protected function roomScopeQuery($query, User $user)
    {
        if ($user->isAttendant()) {
            return $query->where('room_id', $user->room_id);
        }

        return $query;
    }

    public function data(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return response()->json($this->buildPayload($request));
    }

    protected function buildPayload(Request $request): array
    {
        $user = $request->user();
        $roomIds = $this->visibleRoomIds($user);

        // Keep the payload bounded: every currently-checked-in record plus a
        // rolling window of history. Prevents the register from growing unbounded.
        $historyMonths = max(1, (int) config('running_rooms.history_months', 6));
        $windowStart = now()->subMonths($historyMonths)->toDateString();

        $records = AttendanceRecord::query()
            ->whereIn('room_id', $roomIds)
            ->where(function ($query) use ($windowStart) {
                $query->where('status', 'in')
                    ->orWhere('arrival_date', '>=', $windowStart)
                    ->orWhere('departure_date', '>=', $windowStart);
            })
            ->orderBy('arrival_date', 'desc')
            ->orderBy('arrival_time', 'desc')
            ->get();

        $matters = Matter::query()
            ->with('photos')
            ->whereIn('room_id', $roomIds)
            ->where('date', '>=', $windowStart)
            ->orderBy('date', 'desc')
            ->get();

        $rooms = Room::query()
            ->whereIn('id', $roomIds)
            ->orderBy('name')
            ->with('usableBeds:id,room_id,bed_no,is_usable')
            ->get(['id', 'name', 'depot_code', 'beds']);

        return [
            'user' => [
                'name' => $user->name ?: $user->username,
                'role' => $user->isAttendant() ? 'attendant' : 'admin',
                'roomId' => $user->isAttendant() ? $user->room_id : null,
            ],
            'rooms' => $rooms->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'depot' => $room->depot_code,
                'beds' => $room->beds,
                'bedList' => $room->usableBeds
                    ->sortBy(fn ($bed) => [(int) preg_replace('/[^0-9]/', '', $bed->bed_no), $bed->bed_no])
                    ->values()
                    ->map(fn ($bed) => ['bed_no' => $bed->bed_no, 'is_usable' => $bed->is_usable]),
            ])->values(),
            'records' => $records,
            'matters' => $matters->map(fn (Matter $matter) => $this->matterPayload($matter))->values(),
            'designations' => $this->designations(),
            'categories' => $this->categories(),
        ];
    }

    protected function matterPayload(Matter $matter): array
    {
        return [
            'id' => $matter->id,
            'room_id' => $matter->room_id,
            'date' => $matter->date?->toDateString(),
            'category' => $matter->category,
            'description' => $matter->description->toHtml(),
            'reported_by' => $matter->reported_by,
            'status' => $matter->status,
            'resolved_date' => $matter->resolved_date?->toDateString(),
            'ticket_no' => $matter->ticket_no,
            'photos' => $matter->photos->map(fn (MatterPhoto $photo) => [
                'id' => $photo->id,
                'url' => asset($photo->filename),
            ])->values(),
        ];
    }

    protected function occupiedOn(Room $room, string $date): int
    {
        return $room->attendanceRecords()
            ->where('arrival_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('status', 'in')
                    ->orWhereNull('departure_date')
                    ->orWhere('departure_date', '>=', $date);
            })
            ->count();
    }

    public function storeRecord(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'staff_no' => 'required|string|max:64',
            'bed_no' => 'nullable|string|max:64',
            'arrival_date' => 'required|date',
            'arrival_time' => 'required|string|max:8',
            'remarks' => 'nullable|string',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        if (! $this->canManageRoom($user, $data['room_id'])) {
            return response()->json(['error' => 'You may only check in to rooms in your own depot.'], 403);
        }

        $staffNo = trim($data['staff_no']);
        $member = CrewLookup::findByStaffNumber($staffNo);

        if (! $member) {
            return response()->json(['error' => "No crew member found with staff number '{$staffNo}'. Only existing crew members may use running rooms."], 422);
        }

        if (! $member['is_active']) {
            return response()->json(['error' => "Crew member {$member['name']} is not an active crew member."], 422);
        }

        if (! $member['room_eligible']) {
            return response()->json(['error' => "Crew member {$member['name']} ({$member['designation']}) does not qualify to use running rooms."], 422);
        }

        $alreadyIn = AttendanceRecord::query()
            ->where('staff_no', $staffNo)
            ->where('status', 'in')
            ->exists();

        if ($alreadyIn) {
            return response()->json(['error' => "Crew member {$member['name']} is already checked in to a room."], 422);
        }

        $room = Room::findOrFail($data['room_id']);

        if ($this->occupiedOn($room, $data['arrival_date']) >= $room->beds) {
            return response()->json(['error' => "{$room->name} is full on {$data['arrival_date']}."], 422);
        }

        $available = $room->availableBedsOn($data['arrival_date']);
        $requestedBed = trim((string) ($data['bed_no'] ?? ''));

        if ($requestedBed === '') {
            $requestedBed = $available[0] ?? '';
        }

        if ($requestedBed === '') {
            return response()->json(['error' => "No available bed was found in {$room->name} on {$data['arrival_date']}."], 422);
        }

        if (! in_array($requestedBed, $available, true)) {
            return response()->json(['error' => "Bed {$requestedBed} is not available in {$room->name} on {$data['arrival_date']}."], 422);
        }

        $data['bed_no'] = $requestedBed;

        try {
            $record = AttendanceRecord::create([
                'room_id' => $room->id,
                'name' => $member['name'],
                'staff_no' => $staffNo,
                'designation' => $member['designation'],
                'bed_no' => $data['bed_no'],
                'arrival_date' => $data['arrival_date'],
                'arrival_time' => $data['arrival_time'],
                'remarks' => $data['remarks'] ?? null,
                'status' => 'in',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $message = match (true) {
                str_contains($e->getMessage(), 'active_staff_no') => "Crew member {$member['name']} is already checked in to a room.",
                str_contains($e->getMessage(), 'active_room_bed') => "Bed {$data['bed_no']} was just taken - please choose another.",
                default => 'The check-in could not be saved because of a conflict. Please try again.',
            };

            if (str_contains($e->getMessage(), 'active_room_bed')) {
                $freshAvailable = $room->fresh()->availableBedsOn($data['arrival_date']);
                $replacementBed = $freshAvailable[0] ?? '';

                if ($replacementBed !== '' && $replacementBed !== $data['bed_no']) {
                    try {
                        $data['bed_no'] = $replacementBed;

                        $record = AttendanceRecord::create([
                            'room_id' => $room->id,
                            'name' => $member['name'],
                            'staff_no' => $staffNo,
                            'designation' => $member['designation'],
                            'bed_no' => $data['bed_no'],
                            'arrival_date' => $data['arrival_date'],
                            'arrival_time' => $data['arrival_time'],
                            'remarks' => $data['remarks'] ?? null,
                            'status' => 'in',
                        ]);
                    } catch (\Illuminate\Database\QueryException $retryException) {
                        $message = match (true) {
                            str_contains($retryException->getMessage(), 'active_staff_no') => "Crew member {$member['name']} is already checked in to a room.",
                            str_contains($retryException->getMessage(), 'active_room_bed') => "Bed {$data['bed_no']} was just taken - please choose another.",
                            default => 'The check-in could not be saved because of a conflict. Please try again.',
                        };
                    }
                }
            }

            if (! isset($record)) {
                return response()->json(['error' => $message], 422);
            }
        }

        $this->syncCrewFromCheckIn($staffNo, $room, $data['arrival_date'], $data['arrival_time']);

        return response()->json($record, 201);
    }

    public function crewLookup(Request $request, string $staffNo)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $member = CrewLookup::findByStaffNumber($staffNo);

        if (! $member) {
            return response()->json(['error' => "No crew member found with staff number '{$staffNo}'."], 404);
        }

        return response()->json(['member' => $member]);
    }

    public function crewSearch(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 12);

        return response()->json(['results' => CrewLookup::search($query, $limit)]);
    }

    /* ── crew record integration ──────────────────────────────────────────────
     * The register and the crew module share the same crew. Checking a crew
     * member into a room starts their rest (crew status → R, restStarted =
     * arrival, away depot = the room's depot) and checking them out ends it
     * (still resting → Standby, marked as checked out). The booking officer of
     * the crew's home depot therefore sees the rest countdown and the
     * checked-out state even when the crew is resting in another depot. */

    protected function combineDateTime(string $date, string $time): string
    {
        $tz = config('app.timezone') ?: 'UTC';

        try {
            return (new \DateTimeImmutable(trim($date).' '.trim($time), new \DateTimeZone($tz)))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.u\Z');
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }

    protected function syncCrewFromCheckIn(string $staffNo, object $room, string $arrivalDate, string $arrivalTime): void
    {
        if (! Schema::hasTable('crew_members') || ! Schema::hasTable('crew_records')) {
            return;
        }

        $member = DB::table('crew_members')->where('staff_number', $staffNo)->first();
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

        $crewDepot = trim((string) ($payload['depot'] ?? $member->depot_code ?? ''));
        $roomDepot = trim((string) ($room->depot_code ?? ''));
        $awayDepot = ($crewDepot !== '' && $roomDepot !== '' && strcasecmp($crewDepot, $roomDepot) !== 0) ? $roomDepot : null;

        $monthKey = substr($arrivalDate, 0, 7);
        $day = (int) substr($arrivalDate, 8, 2);
        $restEligible = CrewLookup::isDesignationRestEligible((string) ($payload['grade'] ?? $member->designation_code ?? ''));

        $payload = array_merge($payload, [
            'rrRoomId' => (int) $room->id,
            'rrRoomName' => (string) $room->name,
            'rrRoomDepot' => $roomDepot,
            'rrCheckedOut' => false,
            'rrCheckedOutAt' => null,
            'lastUpdated' => now()->toIso8601String(),
            'updatedBy' => 'running-room',
        ]);

        if (! $restEligible) {
            // Non-rest room users (e.g. shunter drivers, PSAs) may use the room
            // without entering the drivers' Resting state on the crew roster.
            DB::table('crew_records')->where('record_id', $member->record_id)->update([
                'payload' => json_encode($payload),
                'updated_at' => now(),
            ]);

            return;
        }

        $monthly = $payload['monthly'] ?? [];
        if (is_array($monthly) && $day >= 1 && $day <= 31) {
            $monthly['d'.$day] = 'R';
        }

        $payload = array_merge($payload, [
            'status' => 'R',
            'since' => $arrivalTime,
            'restStarted' => $this->combineDateTime($arrivalDate, $arrivalTime),
            'awayDepot' => $awayDepot,
            'monthly' => $monthly,
        ]);

        DB::table('crew_records')->where('record_id', $member->record_id)->update([
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);

        $this->closeOpenSegment($member->record_id, $monthKey, $day, $arrivalTime);
        $this->upsertRoomRestSegment($member->record_id, $monthKey, $day, 'R', $arrivalTime, $awayDepot, 'Running room: '.$room->name);
    }

    protected function syncCrewFromCheckOut(string $staffNo, object $record, string $departureDate, string $departureTime): void
    {
        if (! Schema::hasTable('crew_members') || ! Schema::hasTable('crew_records')) {
            return;
        }

        $member = DB::table('crew_members')->where('staff_number', $staffNo)->first();
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
            'rrCheckedOutAt' => $this->combineDateTime($departureDate, $departureTime),
            'lastUpdated' => now()->toIso8601String(),
            'updatedBy' => 'running-room',
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

        if ($wasResting) {
            $monthKey = substr($departureDate, 0, 7);
            $day = (int) substr($departureDate, 8, 2);
            $this->closeOpenSegment($member->record_id, $monthKey, $day, $departureTime);
            $this->upsertRoomRestSegment($member->record_id, $monthKey, $day, 'SB', $departureTime, null, 'Checked out of running room');
        }
    }

    /**
     * Crew-caused cancellation of a Booked assignment (no-show / late / sick
     * without cover). Unlike a normal checkout (which marks the crew Standby,
     * as if actively working), this records ABS or NTB on the crew's day and
     * clears any scheduled pendingBooking so the crew report shows the
     * cancellation rather than inflating Standby.
     */
    protected function syncCrewFromCancellation(string $staffNo, object $record, string $departureDate, string $departureTime, string $reason): void
    {
        if (! Schema::hasTable('crew_members') || ! Schema::hasTable('crew_records')) {
            return;
        }

        $member = DB::table('crew_members')->where('staff_number', $staffNo)->first();
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

        $code = match ($reason) {
            'no-show' => 'ABS',
            'late', 'sick', 'no_cover' => 'NTB',
            default => 'ABS',
        };

        $note = match ($reason) {
            'no-show' => 'No-show for scheduled booking - Absent',
            'late' => 'Late for scheduled booking - Not to board',
            'sick', 'no_cover' => 'Sick without cover - Not to board',
            default => 'Crew-caused cancellation',
        };

        $day = (int) substr($departureDate, 8, 2);
        $monthKey = substr($departureDate, 0, 7);

        $monthly = $payload['monthly'] ?? [];
        if (! is_array($monthly)) {
            $monthly = [];
        }
        $monthly['d'.$day] = $code;

        $payload = array_merge($payload, [
            'status' => $code,
            'since' => $departureTime,
            'bookTime' => $departureTime,
            'restStarted' => null,
            'awayDepot' => null,
            'monthly' => $monthly,
            'rrCheckedOut' => true,
            'rrCheckedOutAt' => $this->combineDateTime($departureDate, $departureTime),
            'cancellationReason' => $reason,
            'notes' => trim((string) ($payload['notes'] ?? '')) === ''
                ? $note
                : trim((string) ($payload['notes'] ?? '')).'; '.$note,
            'lastUpdated' => now()->toIso8601String(),
            'updatedBy' => 'running-room-cancel',
        ]);
        unset($payload['pendingBooking']);

        DB::table('crew_records')->where('record_id', $member->record_id)->update([
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);

        // The crew was absent / not-to-board for the whole day, so the segment
        // covers 00:00 rather than starting at the checkout time.
        $this->closeOpenSegment($member->record_id, $monthKey, $day, '00:00');
        $this->upsertRoomRestSegment($member->record_id, $monthKey, $day, $code, '00:00', null, $note);
    }

    /** Close a still-open (default 23:59) trailing segment at the given time. */
    protected function closeOpenSegment(string $recordId, string $monthKey, int $day, string $time): void
    {
        if (! Schema::hasTable('crew_status_segments') || trim($time) === '') {
            return;
        }

        $latest = DB::table('crew_status_segments')
            ->where('crew_record_id', $recordId)
            ->where('month_key', $monthKey)
            ->where('day', $day)
            ->orderByDesc('sort_order')
            ->first();

        if ($latest && trim((string) $latest->end_time) === '23:59') {
            DB::table('crew_status_segments')->where('segment_id', $latest->segment_id)
                ->update(['end_time' => trim($time), 'updated_at' => now()]);
        }
    }

    /** Stack a rest segment for the crew's day timeline, mirroring the crew app. */
    protected function upsertRoomRestSegment(string $recordId, string $monthKey, int $day, string $statusCode, string $startTime, ?string $awayDepot, string $note): void
    {
        if (! Schema::hasTable('crew_status_segments')) {
            return;
        }

        $currentMonthKey = now()->format('Y-m');
        if ($monthKey !== $currentMonthKey || $day < 1 || $day > 31) {
            return;
        }

        $maxOrder = (int) DB::table('crew_status_segments')
            ->where('crew_record_id', $recordId)
            ->where('month_key', $monthKey)
            ->where('day', $day)
            ->max('sort_order');

        $sortOrder = $maxOrder + 100;

        DB::table('crew_status_segments')->updateOrInsert(
            ['segment_id' => (string) Str::uuid()],
            [
                'crew_record_id' => $recordId,
                'month_key' => $monthKey,
                'day' => $day,
                'sort_order' => $sortOrder,
                'status_code' => $statusCode,
                'start_time' => trim($startTime) ?: '00:00',
                'end_time' => '23:59',
                'away_depot' => $awayDepot,
                'notes' => $note,
                'metadata' => json_encode(['source' => 'running-room']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /** Merge a designation list from the settings editor into the shared crew list. */
    protected function syncSharedDesignations(array $names): void
    {
        if (! Schema::hasTable('designations')) {
            return;
        }

        $now = now();
        $existing = DB::table('designations')->get()->keyBy(fn ($row) => mb_strtolower(trim((string) $row->designation_name)));

        foreach ($names as $name) {
            $key = mb_strtolower(trim((string) $name));
            $row = $existing->get($key);

            if ($row) {
                DB::table('designations')->where('designation_code', $row->designation_code)
                    ->update(['is_active' => true, 'updated_at' => $now]);

                continue;
            }

            $code = CrewLookup::normalizeDesignationKey($name);
            if ($code === '') {
                $code = 'd_'.substr(md5((string) $name), 0, 8);
            }

            DB::table('designations')->updateOrInsert(
                ['designation_code' => $code],
                [
                    'designation_name' => trim((string) $name),
                    'sort_order' => 0,
                    'is_active' => true,
                    'metadata' => json_encode(['restEligible' => true, 'runningRoomEligible' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('running_room_options')->where('key', 'designations')->delete();
        Cache::forget('running_room_options:designations');
    }

    public function checkoutRecord(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $record = AttendanceRecord::findOrFail($id);

        if (! $this->canManageRoom($user, $record->room_id)) {
            return response()->json(['error' => 'You may only check out crew from rooms in your own depot.'], 403);
        }

        $v = Validator::make($request->all(), [
            'departure_date' => 'nullable|date',
            'departure_time' => 'nullable|string|max:8',
            'cancellation' => 'nullable|string|in:no-show,late,sick,no_cover',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $departureDate = $data['departure_date'] ?? now()->toDateString();
        $departureTime = $data['departure_time'] ?? now()->format('H:i');
        $record->checkOut($departureDate, $departureTime);

        $cancellation = trim((string) ($data['cancellation'] ?? ''));
        if ($cancellation !== '') {
            // Crew-caused cancellation (no-show / late / sick without cover):
            // record ABS/NTB so the daily status does not inflate Standby as if
            // the crew was actively working.
            $this->syncCrewFromCancellation($record->staff_no, $record, $departureDate, $departureTime, $cancellation);
        } else {
            $this->syncCrewFromCheckOut($record->staff_no, $record, $departureDate, $departureTime);
        }

        return response()->json($record);
    }

    public function deleteRecord(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $record = AttendanceRecord::findOrFail($id);

        if (! $this->canManageRoom($user, $record->room_id)) {
            return response()->json(['error' => 'You may only delete records from rooms in your own depot.'], 403);
        }

        $wasIn = $record->status === 'in';
        $record->delete();

        if ($wasIn && $record->staff_no) {
            $this->syncCrewFromCheckOut($record->staff_no, $record, now()->toDateString(), now()->format('H:i'));
        }

        return response()->json(['deleted' => true]);
    }

    public function storeMatter(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date',
            'category' => 'required|string|in:' . implode(',', $this->categories()),
            'description' => 'required|string',
            'reported_by' => 'nullable|string|max:128',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        if (! $this->canManageRoom($user, $data['room_id'])) {
            return response()->json(['error' => 'You may only log matters for rooms in your own depot.'], 403);
        }

        $matter = Matter::create([
            'room_id' => $data['room_id'],
            'date' => $data['date'],
            'category' => $data['category'],
            'description' => $data['description'],
            'reported_by' => $data['reported_by'] ?? $user->name ?: $user->username,
            'status' => 'open',
        ]);

        $photos = $request->file('photos', []);
        if (! empty($photos)) {
            $this->storeMatterPhotoFiles($matter, $photos);
        }

        $matter->load('photos');

        return response()->json($this->matterPayload($matter), 201);
    }

    public function updateMatter(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $matter = Matter::findOrFail($id);

        if (! $this->canManageRoom($user, $matter->room_id)) {
            return response()->json(['error' => 'You may only edit matters for rooms in your own depot.'], 403);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date',
            'category' => 'required|string|in:' . implode(',', $this->categories()),
            'description' => 'required|string',
            'reported_by' => 'nullable|string|max:128',
            'status' => 'required|string|in:open,resolved',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'photo_deletes' => 'nullable|array',
            'photo_deletes.*' => 'integer',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        if ($user->isAttendant() && (int) $data['room_id'] !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only edit matters for their own room.'], 403);
        }

        $matter->update([
            'room_id' => $data['room_id'],
            'date' => $data['date'],
            'category' => $data['category'],
            'description' => $data['description'],
            'reported_by' => $data['reported_by'],
            'status' => $data['status'],
            'resolved_date' => $data['status'] === 'resolved'
                ? ($matter->resolved_date ?? now()->toDateString())
                : null,
        ]);

        $deleteIds = array_map('intval', $data['photo_deletes'] ?? []);
        if (! empty($deleteIds)) {
            foreach ($matter->photos()->whereIn('id', $deleteIds)->get() as $photo) {
                $this->deletePhotoFile($photo);
                $photo->delete();
            }
        }

        $photos = $request->file('photos', []);
        if (! empty($photos)) {
            $this->storeMatterPhotoFiles($matter, $photos);
        }

        $matter->load('photos');

        return response()->json($this->matterPayload($matter));
    }

    public function deleteMatter(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $matter = Matter::findOrFail($id);

        if (! $this->canManageRoom($user, $matter->room_id)) {
            return response()->json(['error' => 'You may only delete matters for rooms in your own depot.'], 403);
        }

        $this->deleteMatterPhotoFiles($matter);

        $matter->delete();

        return response()->json(['deleted' => true]);
    }

    public function updateBeds(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isRoomAdmin()) {
            return response()->json(['error' => 'Only admins may change bed capacity.'], 403);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'beds' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $room = Room::findOrFail($v->validated()['room_id']);
        $beds = (int) $v->validated()['beds'];
        $occupied = $room->occupiedCount();

        $room->update(['beds' => $beds]);

        return response()->json([
            'room' => $room,
            'warning' => $occupied > $beds ? "{$room->name} has {$occupied} people checked in, above the new capacity of {$beds}." : null,
        ]);
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $items = SystemNotification::query()
            ->forRecipient($user->username)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unread = $items->filter(fn (SystemNotification $n) => $n->read_at === null)->count();

        return response()->json([
            'unread_count' => $unread,
            'notifications' => $items->map(fn (SystemNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'data' => $n->data,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function markNotificationsRead(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $ids = $request->input('ids');
        if (is_array($ids) && ! empty($ids)) {
            $count = SystemNotification::query()
                ->forRecipient($user->username)
                ->unread()
                ->whereIn('id', array_map('intval', $ids))
                ->update(['read_at' => now()]);
        } else {
            $count = SystemNotification::query()
                ->forRecipient($user->username)
                ->unread()
                ->update(['read_at' => now()]);
        }

        return response()->json(['updated' => $count]);
    }

    /**
     * Token-protected webhook that runs the rest-expiry auto-checkout directly.
     * Used on shared hosts that have no cron: a free ping service (cron-job.org)
     * hits /running-rooms/cron/auto-checkout?token=... every five minutes.
     */
    public function cronAutoCheckout(Request $request)
    {
        $provided = (string) ($request->query('token') ?? '');
        $expected = (string) config('cron.token');

        if ($expected === '' || $expected === 'change-me' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        try {
            Artisan::call('running-rooms:auto-checkout-rested');

            // Promote scheduled crew bookings whose departure time has arrived
            // (and deliver the T-1hr booking notices) on the same cron webhook.
            Artisan::call('crew:promote-pending-bookings');

            // Also respect any scheduled end time for maintenance mode, so the
            // cron webhook covers shared hosts without a real scheduler.
            Artisan::call('maintenance:auto-deactivate');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'output' => Artisan::output()]);
    }

    public function updateOptions(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isRoomAdmin()) {
            return response()->json(['error' => 'Only admins may change register options.'], 403);
        }

        $v = Validator::make($request->all(), [
            'designations' => 'nullable|array',
            'designations.*' => 'required|string|max:64',
            'categories' => 'nullable|array',
            'categories.*' => 'required|string|max:64',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        if (array_key_exists('designations', $data)) {
            $names = array_values(array_unique(array_filter(array_map('trim', (array) $data['designations']), fn ($value) => $value !== '')));

            if (empty($names)) {
                return response()->json(['error' => 'Designations must contain at least one value.'], 422);
            }

            // Designations are shared with the crew module — save them into the
            // same designations table the crew app uses instead of a register-only list.
            $this->syncSharedDesignations($names);
        }

        if (array_key_exists('categories', $data)) {
            $values = array_values(array_unique(array_map('trim', $data['categories'])));
            $values = array_values(array_filter($values, fn ($value) => $value !== ''));

            if (empty($values)) {
                return response()->json(['error' => 'Categories must contain at least one value.'], 422);
            }

            DB::table('running_room_options')->updateOrInsert(
                ['key' => 'categories'],
                ['options' => json_encode($values), 'created_at' => now(), 'updated_at' => now()]
            );

            Cache::forget('running_room_options:categories');
            self::$cachedCategories = null;
        }

        return response()->json(['saved' => true]);
    }
}
