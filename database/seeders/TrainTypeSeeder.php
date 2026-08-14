<?php

namespace Database\Seeders;

use App\Models\TrainType;
use Illuminate\Database\Seeder;

class TrainTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'train_type_code' => 'freight',
                'train_type_name' => 'Freight',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'train_type_code' => 'commuter',
                'train_type_name' => 'Commuter',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'train_type_code' => 'passenger',
                'train_type_name' => 'Passenger',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'train_type_code' => 'engineering',
                'train_type_name' => 'Engineering Train',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'train_type_code' => 'shunting',
                'train_type_name' => 'Shunting',
                'sort_order' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($types as $data) {
            TrainType::updateOrCreate(
                ['train_type_code' => $data['train_type_code']],
                $data
            );
        }
    }
}
