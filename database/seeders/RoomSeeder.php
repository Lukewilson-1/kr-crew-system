<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Nairobi', 'beds' => 14],
            ['name' => 'Longonot', 'beds' => 4],
            ['name' => 'Mtito Andei', 'beds' => 24],
            ['name' => 'Nakuru', 'beds' => 26],
            ['name' => 'Nanyuki', 'beds' => 9],
            ['name' => 'Malaba', 'beds' => 14],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['name' => $room['name']],
                [
                    'beds' => $room['beds'],
                    'password' => Hash::make(Str::lower(preg_replace('/[^a-z]/i', '', $room['name'])).'123'),
                ]
            );
        }
    }
}
