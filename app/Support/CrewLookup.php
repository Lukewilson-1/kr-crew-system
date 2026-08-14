<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared crew data access used by the crew app and the running room register.
 *
 * Crew members live in the crew_members table (synced from crew_records). The
 * register only ever references a crew member by staff number and must make
 * sure the member exists and is rest-eligible before allocating a bed.
 */
class CrewLookup
{
    /** Look up a crew member by staff number. Returns a slim payload or null. */
    public static function findByStaffNumber(string $staffNo): ?array
    {
        $staffNo = trim($staffNo);

        if ($staffNo === '' || ! Schema::hasTable('crew_members')) {
            return null;
        }

        $member = DB::table('crew_members')
            ->where('staff_number', $staffNo)
            ->first();

        if (! $member) {
            return null;
        }

        return self::slimMember($member);
    }

    /**
     * Search active crew members by staff number or name (partial match).
     * Returns slim payloads ordered by name. Empty/short queries return [].
     */
    public static function search(string $query, int $limit = 10): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2 || ! Schema::hasTable('crew_members')) {
            return [];
        }

        $escaped = str_replace(['%', '\\'], ['\%', '\\\\'], $query);
        $needle = '%'.$escaped.'%';
        $prefix = $escaped.'%';
        $members = DB::table('crew_members')
            ->where('is_active', 1)
            ->where(function ($builder) use ($needle) {
                $builder->where('staff_number', 'like', $needle)
                    ->orWhere('display_name', 'like', $needle)
                    ->orWhere('first_name', 'like', $needle)
                    ->orWhere('last_name', 'like', $needle)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$needle]);
            })
            ->orderByRaw('CASE WHEN staff_number = ? THEN 0 WHEN staff_number LIKE ? THEN 1 ELSE 2 END', [$query, $prefix])
            ->orderBy('display_name')
            ->orderBy('first_name')
            ->orderBy('staff_number')
            ->limit(max(1, min(50, $limit)))
            ->get();

        return $members->map(fn ($member) => self::slimMember($member))->values()->all();
    }

    protected static function slimMember(object $member): array
    {
        return [
            'record_id' => (string) ($member->record_id ?? ''),
            'staff_no' => (string) $member->staff_number,
            'name' => (string) ($member->display_name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))),
            'designation' => (string) ($member->designation_code ?? ''),
            'depot' => (string) ($member->depot_code ?? ''),
            'is_active' => (bool) $member->is_active,
            'rest_eligible' => self::isDesignationRestEligible($member->designation_code ?? ''),
            'room_eligible' => self::canUseRunningRooms($member->designation_code ?? ''),
        ];
    }

    public static function normalizeDesignationKey(mixed $value): string
    {
        $key = strtolower(trim((string) $value));
        $key = str_replace(["'", "\u{2019}"], '', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?: '';

        return trim($key, '_');
    }

    /** Read the designation metadata row (matched by code or name). */
    protected static function designationMeta(mixed $designation): ?array
    {
        $designation = trim((string) $designation);

        if ($designation === '' || ! Schema::hasTable('designations')) {
            return null;
        }

        $row = DB::table('designations')
            ->whereRaw('LOWER(designation_code) = ?', [strtolower($designation)])
            ->orWhereRaw('LOWER(designation_name) = ?', [strtolower($designation)])
            ->first();

        if (! $row) {
            return null;
        }

        return json_decode($row->metadata ?? '{}', true) ?: [];
    }

    /**
     * Whether a designation may be put on Resting (drivers' statutory rest).
     * Driven entirely by the designation permission flags — no hardcoding.
     */
    public static function isDesignationRestEligible(mixed $designation): bool
    {
        $metadata = self::designationMeta($designation);

        return $metadata === null ? true : data_get($metadata, 'restEligible', true) !== false;
    }

    /**
     * Whether a designation may be checked in to a running room. Separate from
     * rest eligibility so e.g. shunter drivers or future PSAs can use rooms
     * without being eligible for the Resting status.
     */
    public static function canUseRunningRooms(mixed $designation): bool
    {
        $metadata = self::designationMeta($designation);

        if ($metadata === null) {
            return true;
        }

        return data_get($metadata, 'runningRoomEligible', data_get($metadata, 'restEligible', true)) !== false;
    }
}
