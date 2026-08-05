<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Depot;

class DepotSeeder extends Seeder
{
    public function run(): void
    {
        $depots = [
            ['depot_code' => 'MKR', 'depot_name' => 'Makadara'],
            ['depot_code' => 'CGW', 'depot_name' => 'Changamwe'],
            ['depot_code' => 'MTO', 'depot_name' => 'MTITO'],
            ['depot_code' => 'NRO', 'depot_name' => 'Nakuru'],
            ['depot_code' => 'ELD', 'depot_name' => 'Eldoret'],
            ['depot_code' => 'KSM', 'depot_name' => 'Kisumu'],
            ['depot_code' => 'MLB', 'depot_name' => 'Malaba'],
            ['depot_code' => 'NUK', 'depot_name' => 'Nanyuki'],
        ];

        foreach ($depots as $data) {
            Depot::updateOrCreate(
                ['depot_code' => $data['depot_code']],
                $data
            );
        }
    }
}
