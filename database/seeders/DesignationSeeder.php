<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignationSeeder extends Seeder
{
    private function normalizeDesignationKey(string $value): string
    {
        $key = strtolower(trim($value));
        $key = str_replace(["'", "\u{2019}"], '', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?: '';
        return trim($key, '_');
    }

    public function run(): void
    {
        $designations = DB::table('crew_members')
            ->whereNotNull('designation_code')
            ->where('designation_code', '<>', '')
            ->distinct()
            ->orderBy('designation_code')
            ->pluck('designation_code')
            ->toArray();

        foreach ($designations as $code) {
            DB::table('designations')->updateOrInsert(
                ['designation_code' => $code],
                [
                    'designation_name' => $code,
                    'sort_order' => 0,
                    'is_active' => true,
                    'metadata' => json_encode([
                        'restEligible' => true,
                        'runningRoomEligible' => true,
                        'canLogin' => true,
                        'isCrewMember' => true,
                        'isUser' => false,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
