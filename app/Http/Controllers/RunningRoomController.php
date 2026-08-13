<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Matter;
use App\Models\Room;
use App\Support\CrewLookup;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RunningRoomController extends Controller
{
    public const DESIGNATIONS = ['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other'];

    public const CATEGORIES = [
        'Maintenance', 'Cleanliness', 'Security',
        'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
    ];

    public function index(Request $request)
    {
        if (! $request->user()) {
            return redirect('/login');
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

    protected function visibleRoomIds(User $user)
    {
        if ($user->isAttendant()) {
            return [$user->room_id];
        }

        return Room::query()->pluck('id')->all();
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
            ->whereIn('room_id', $roomIds)
            ->where('date', '>=', $windowStart)
            ->orderBy('date', 'desc')
            ->get();

        $rooms = Room::query()
            ->whereIn('id', $roomIds)
            ->orderBy('name')
            ->with('usableBeds:id,room_id,bed_no,is_usable')
            ->get(['id', 'name', 'beds']);

        return [
            'user' => [
                'name' => $user->name ?: $user->username,
                'role' => $user->isAttendant() ? 'attendant' : 'admin',
                'roomId' => $user->isAttendant() ? $user->room_id : null,
            ],
            'rooms' => $rooms->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'beds' => $room->beds,
                'bedList' => $room->usableBeds
                    ->sortBy(fn ($bed) => [(int) preg_replace('/[^0-9]/', '', $bed->bed_no), $bed->bed_no])
                    ->values()
                    ->map(fn ($bed) => ['bed_no' => $bed->bed_no, 'is_usable' => $bed->is_usable]),
            ])->values(),
            'records' => $records,
            'matters' => $matters,
            'designations' => static::DESIGNATIONS,
            'categories' => static::CATEGORIES,
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

        if ($user->isAttendant() && (int) $data['room_id'] !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only check in to their own room.'], 403);
        }

        $staffNo = trim($data['staff_no']);
        $member = CrewLookup::findByStaffNumber($staffNo);

        if (! $member) {
            return response()->json(['error' => "No crew member found with staff number '{$staffNo}'. Only existing crew members may use running rooms."], 422);
        }

        if (! $member['is_active']) {
            return response()->json(['error' => "Crew member {$member['name']} is not an active crew member."], 422);
        }

        if (! $member['rest_eligible']) {
            return response()->json(['error' => "Crew member {$member['name']} ({$member['designation']}) does not qualify for rest rooms."], 422);
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

        if ($data['bed_no'] !== null && $data['bed_no'] !== '') {
            $available = $room->availableBedsOn($data['arrival_date']);

            if (! in_array($data['bed_no'], $available, true)) {
                return response()->json(['error' => "Bed {$data['bed_no']} is not available in {$room->name} on {$data['arrival_date']}."], 422);
            }
        }

        try {
            $record = AttendanceRecord::create([
                'room_id' => $room->id,
                'name' => $member['name'],
                'staff_no' => $staffNo,
                'designation' => $member['designation'],
                'bed_no' => $data['bed_no'] ?? null,
                'arrival_date' => $data['arrival_date'],
                'arrival_time' => $data['arrival_time'],
                'remarks' => $data['remarks'] ?? null,
                'status' => 'in',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $message = match (true) {
                str_contains($e->getMessage(), 'active_staff_no') => "Crew member {$member['name']} is already checked in to a room.",
                str_contains($e->getMessage(), 'active_room_bed') => "Bed {$data['bed_no']} was just taken — please choose another.",
                default => 'The check-in could not be saved because of a conflict. Please try again.',
            };

            return response()->json(['error' => $message], 422);
        }

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

    public function checkoutRecord(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $record = AttendanceRecord::findOrFail($id);

        if ($user->isAttendant() && (int) $record->room_id !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only check out from their own room.'], 403);
        }

        $v = Validator::make($request->all(), [
            'departure_date' => 'nullable|date',
            'departure_time' => 'nullable|string|max:8',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $record->checkOut($data['departure_date'] ?? null, $data['departure_time'] ?? null);

        return response()->json($record);
    }

    public function deleteRecord(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $record = AttendanceRecord::findOrFail($id);

        if ($user->isAttendant() && (int) $record->room_id !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only delete records from their own room.'], 403);
        }

        $record->delete();

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
            'category' => 'required|string|in:' . implode(',', static::CATEGORIES),
            'description' => 'required|string',
            'reported_by' => 'nullable|string|max:128',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        if ($user->isAttendant() && (int) $data['room_id'] !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only log matters for their own room.'], 403);
        }

        $matter = Matter::create([
            'room_id' => $data['room_id'],
            'date' => $data['date'],
            'category' => $data['category'],
            'description' => $data['description'],
            'reported_by' => $data['reported_by'] ?? $user->name ?: $user->username,
            'status' => 'open',
        ]);

        return response()->json($matter, 201);
    }

    public function updateMatter(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $matter = Matter::findOrFail($id);

        if ($user->isAttendant() && (int) $matter->room_id !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only edit matters for their own room.'], 403);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date',
            'category' => 'required|string|in:' . implode(',', static::CATEGORIES),
            'description' => 'required|string',
            'reported_by' => 'nullable|string|max:128',
            'status' => 'required|string|in:open,resolved',
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

        return response()->json($matter);
    }

    public function deleteMatter(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $matter = Matter::findOrFail($id);

        if ($user->isAttendant() && (int) $matter->room_id !== (int) $user->room_id) {
            return response()->json(['error' => 'Attendants may only delete matters for their own room.'], 403);
        }

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

    public function resetRoomPassword(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isRoomAdmin()) {
            return response()->json(['error' => 'Only admins may reset room passwords.'], 403);
        }

        $v = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'password' => 'required|string|min:6',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        User::query()
            ->where('room_id', $v->validated()['room_id'])
            ->where('role', 'attendant')
            ->update(['password' => Hash::make($v->validated()['password'])]);

        return response()->json(['saved' => true]);
    }
}
