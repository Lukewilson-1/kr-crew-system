<?php

use App\Support\CrewLookup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function upsertMeta(string $code, string $name, array $meta): void
    {
        $existing = DB::table('designations')->where('designation_code', $code)->first();
        $merged = $existing ? (json_decode($existing->metadata ?? '{}', true) ?: []) : [];

        foreach ($meta as $key => $value) {
            $merged[$key] = $value;
        }

        if (! isset($merged['active'])) {
            $merged['active'] = true;
        }

        $values = [
            'designation_name' => $name,
            'is_active' => true,
            'metadata' => json_encode($merged),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('designations')->where('designation_code', $code)->update($values);

            return;
        }

        $values['designation_code'] = $code;
        $values['sort_order'] = 999;
        $values['created_at'] = now();
        DB::table('designations')->insert($values);
    }

    public function up(): void
    {
        if (! Schema::hasTable('designations')) {
            return;
        }

        foreach (DB::table('designations')->get() as $row) {
            $codeKey = CrewLookup::normalizeDesignationKey((string) $row->designation_code);
            $nameKey = CrewLookup::normalizeDesignationKey((string) $row->designation_name);

            if (in_array($codeKey, ['driver', 'locomotive_driver', 'ld', 'rsf', 'ta', 'conductor', 'guard'], true)
                || in_array($nameKey, ['driver', 'locomotive_driver', 'running_shift_foremen', 'train_assistant', 'conductor', 'guard'], true)) {
                $this->upsertMeta((string) $row->designation_code, (string) $row->designation_name, [
                    'restEligible' => true,
                    'runningRoomEligible' => true,
                ]);
            }

            if (in_array($codeKey, ['shunter', 'shunter_driver', 'sd'], true)
                || in_array($nameKey, ['shunter', 'shunter_driver'], true)) {
                // Shunter drivers may use running rooms between trips but are not
                // eligible for the drivers' Resting status.
                $this->upsertMeta((string) $row->designation_code, (string) $row->designation_name, [
                    'restEligible' => false,
                    'runningRoomEligible' => true,
                ]);
            }

            if ($codeKey === 'lio' || $nameKey === 'lio') {
                $this->upsertMeta((string) $row->designation_code, (string) $row->designation_name, [
                    'restEligible' => false,
                    'runningRoomEligible' => false,
                ]);
            }

            if (in_array($codeKey, ['booking_officer', 'inspector', 'station_officer', 'hq_admin', 'super_admin'], true)) {
                $this->upsertMeta((string) $row->designation_code, (string) $row->designation_name, [
                    'restEligible' => false,
                    'runningRoomEligible' => false,
                ]);
            }
        }

        // Passenger Service Attendants: ticketing staff who may use a depot's
        // running room between trips. Not Resting-eligible until they are added
        // to the crew management system for planning.
        $this->upsertMeta('PSA', 'Passenger Service Attendant', [
            'aliases' => ['passenger_service_attendant'],
            'restEligible' => false,
            'runningRoomEligible' => true,
            'canLogin' => true,
            'isCrewMember' => true,
            'isUser' => false,
            'active' => true,
        ]);
    }

    public function down(): void
    {
        // Intentionally not reverted: permissions are a policy decision.
    }
};
