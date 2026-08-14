<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReportDefinitionSeeder::class,
            DepotSeeder::class,
            ShiftTemplateSeeder::class,
            TrainTypeSeeder::class,
            DepotUserSeeder::class,
            RoomSeeder::class,
            RunningRoomSampleSeeder::class,
            RunningRoomOptionSeeder::class,
        ]);
    }
}
