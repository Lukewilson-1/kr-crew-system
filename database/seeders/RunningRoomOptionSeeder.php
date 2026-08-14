<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RunningRoomOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            'designations' => ['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other'],
            'categories' => [
                'Maintenance', 'Cleanliness', 'Security',
                'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
            ],
        ];

        foreach ($options as $key => $values) {
            DB::table('running_room_options')->updateOrInsert(
                ['key' => $key],
                [
                    'options' => json_encode($values),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
