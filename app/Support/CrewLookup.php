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

        return [
            'staff_no' => (string) $member->staff_number,
            'name' => (string) ($member->display_name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))),
            'designation' => (string) ($member->designation_code ?? ''),
            'depot' => (string) ($member->depot_code ?? ''),
            'is_active' => (bool) $member->is_active,
            'rest_eligible' => self::isDesignationRestEligible($member->designation_code ?? ''),
        ];
    }

    public static function normalizeDesignationKey(mixed $value): string
    {
        $key = strtolower(trim((string) $value));
        $key = str_replace(["'", "\u{2019}"], '', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?: '';

        return trim($key, '_');
    }

    /** Whether a designation qualifies for rest rooms (shared with the crew app). */
    public static function isDesignationRestEligible(mixed $designation): bool
    {
        $key = self::normalizeDesignationKey($designation);

        if ($key === '') {
            return false;
        }

        if (in_array($key, ['shunter_driver', 'shunter', 'lio'], true)) {
            return false;
        }

        if (! Schema::hasTable('designations')) {
            return true;
        }

        $row = DB::table('designations')
            ->whereRaw('LOWER(designation_code) = ?', [strtolower((string) $designation)])
            ->orWhereRaw('LOWER(designation_name) = ?', [strtolower((string) $designation)])
            ->first();

        if (! $row) {
            return true;
        }

        if (self::normalizeDesignationKey($row->designation_code) === 'shunter_driver'
            || self::normalizeDesignationKey($row->designation_name) === 'shunter_driver'
            || in_array(self::normalizeDesignationKey($row->designation_code), ['shunter', 'lio'], true)
            || in_array(self::normalizeDesignationKey($row->designation_name), ['shunter', 'lio'], true)) {
            return false;
        }

        $metadata = json_decode($row->metadata ?? '{}', true) ?: [];

        return data_get($metadata, 'restEligible', true) !== false;
    }
}
