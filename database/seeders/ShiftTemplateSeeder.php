<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftTemplate;

class ShiftTemplateSeeder extends Seeder
{
	public function run(): void
	{
		$templates = [
			[
				'shift_code' => 'day',
				'shift_name' => 'Day',
				'starts_at' => '06:00:00',
				'ends_at' => '18:00:00',
				'sort_order' => 10,
				'is_active' => true,
			],
			[
				'shift_code' => 'night',
				'shift_name' => 'Night',
				'starts_at' => '18:00:00',
				'ends_at' => '06:00:00',
				'sort_order' => 20,
				'is_active' => true,
			],
		];

		foreach ($templates as $data) {
			ShiftTemplate::updateOrCreate(
				['shift_code' => $data['shift_code']],
				$data
			);
		}
	}
}
