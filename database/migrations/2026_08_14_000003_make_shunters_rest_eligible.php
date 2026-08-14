<?php

use App\Support\CrewLookup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('designations')) {
            return;
        }

        $rows = DB::table('designations')->get();

        foreach ($rows as $row) {
            $codeKey = CrewLookup::normalizeDesignationKey((string) $row->designation_code);
            $nameKey = CrewLookup::normalizeDesignationKey((string) $row->designation_name);

            if (in_array($codeKey, ['shunter', 'shunter_driver'], true)
                || in_array($nameKey, ['shunter', 'shunter_driver'], true)) {
                $metadata = json_decode($row->metadata ?? '{}', true) ?: [];
                $metadata['restEligible'] = true;
                $metadata['active'] = true;

                DB::table('designations')
                    ->where('designation_code', $row->designation_code)
                    ->update(['metadata' => json_encode($metadata), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally not reverted: rest eligibility is a policy decision.
    }
};
