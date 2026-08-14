<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AttendanceRecordResource;
use App\Filament\Resources\MatterResource;
use App\Filament\Resources\RoomResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RunningRoomOperationsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.running-room-operations-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $roomTotal = $this->tableExists('rooms') ? DB::table('rooms')->count() : 0;
        $bedTotal = $this->tableExists('rooms') ? (int) DB::table('rooms')->sum('beds') : 0;
        $occupiedBeds = $this->tableExists('attendance_records')
            ? DB::table('attendance_records')->where('status', 'in')->count()
            : 0;
        $vacantBeds = max(0, $bedTotal - $occupiedBeds);
        $guestsIn = $occupiedBeds;
        $matterTotal = $this->tableExists('matters') ? DB::table('matters')->count() : 0;
        $openMatters = $this->tableExists('matters')
            ? DB::table('matters')->where('status', 'open')->count()
            : 0;
        $resolvedMatters = max(0, $matterTotal - $openMatters);
        $fullRooms = $this->tableExists('rooms')
            ? DB::table('rooms')->get(['id', 'beds'])->filter(function (object $room): bool {
                $occupied = DB::table('attendance_records')->where('room_id', $room->id)->where('status', 'in')->count();

                return (int) $room->beds > 0 && $occupied >= (int) $room->beds;
            })->count()
            : 0;

        return [
            'heroStats' => [
                ['label' => 'Running rooms', 'value' => number_format($roomTotal), 'hint' => 'Configured rooms', 'tone' => 'blue'],
                ['label' => 'Guests checked in', 'value' => number_format($guestsIn), 'hint' => 'Currently occupying beds', 'tone' => 'emerald'],
                ['label' => 'Vacant beds', 'value' => number_format($vacantBeds), 'hint' => 'of ' . number_format($bedTotal) . ' total', 'tone' => 'amber'],
                ['label' => 'Full rooms', 'value' => number_format($fullRooms), 'hint' => 'At or above capacity', 'tone' => 'red'],
            ],
            'quickActions' => [
                ['label' => 'Check in guest', 'href' => AttendanceRecordResource::getUrl('create'), 'description' => 'Log a room check-in'],
                ['label' => 'Log matter', 'href' => MatterResource::getUrl('create'), 'description' => 'Report an issue'],
                ['label' => 'Manage rooms', 'href' => RoomResource::getUrl('index'), 'description' => 'Review room setup'],
                ['label' => 'Check-in / Out', 'href' => AttendanceRecordResource::getUrl('index'), 'description' => 'Browse attendance'],
            ],
            'roomRows' => $this->roomRows(),
            'matterRows' => $this->matterRows(),
            'recentCheckins' => $this->recentCheckins(),
            'matterStats' => [
                ['label' => 'Total matters', 'value' => number_format($matterTotal)],
                ['label' => 'Open', 'value' => number_format($openMatters)],
                ['label' => 'Resolved', 'value' => number_format($resolvedMatters)],
            ],
        ];
    }

    private function roomRows(): array
    {
        if (! $this->tableExists('rooms')) {
            return [];
        }

        return DB::table('rooms')
            ->orderBy('name')
            ->get(['id', 'name', 'beds'])
            ->map(function (object $row): array {
                $occupied = DB::table('attendance_records')->where('room_id', $row->id)->where('status', 'in')->count();
                $vacant = max(0, (int) $row->beds - $occupied);
                $open = DB::table('matters')->where('room_id', $row->id)->where('status', 'open')->count();

                return [
                    'name' => $row->name,
                    'beds' => (int) $row->beds,
                    'occupied' => $occupied,
                    'vacant' => $vacant,
                    'open' => $open,
                    'percent' => (int) $row->beds > 0 ? round(($occupied / (int) $row->beds) * 100) : 0,
                    'full' => (int) $row->beds > 0 && $occupied >= (int) $row->beds,
                ];
            })
            ->toArray();
    }

    private function matterRows(): array
    {
        if (! $this->tableExists('matters')) {
            return [];
        }

        return DB::table('matters')
            ->leftJoin('rooms', 'rooms.id', '=', 'matters.room_id')
            ->orderByDesc('matters.created_at')
            ->limit(6)
            ->get([
                'matters.ticket_no',
                'matters.category',
                'matters.status',
                'matters.description',
                'rooms.name as room_name',
            ])
            ->map(fn (object $row): array => [
                'ticket' => $row->ticket_no,
                'room' => $row->room_name ?: 'Unassigned',
                'category' => $row->category,
                'status' => $row->status,
                'description' => $row->description,
            ])
            ->toArray();
    }

    private function recentCheckins(): array
    {
        if (! $this->tableExists('attendance_records')) {
            return [];
        }

        return DB::table('attendance_records')
            ->leftJoin('rooms', 'rooms.id', '=', 'attendance_records.room_id')
            ->orderByDesc('attendance_records.created_at')
            ->limit(6)
            ->get([
                'attendance_records.name',
                'attendance_records.designation',
                'attendance_records.bed_no',
                'attendance_records.status',
                'attendance_records.arrival_date',
                'rooms.name as room_name',
            ])
            ->map(fn (object $row): array => [
                'name' => $row->name ?: 'Unnamed guest',
                'room' => $row->room_name ?: 'No room',
                'meta' => trim(($row->designation ?: '') . ($row->bed_no ? ' / Bed ' . $row->bed_no : '')),
                'status' => $row->status,
                'time' => $row->arrival_date,
            ])
            ->toArray();
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
