<?php

namespace Database\Seeders;

use App\Models\ShiftPreset;
use Illuminate\Database\Seeder;

class ShiftPresetSeeder extends Seeder
{
    public function run(): void
    {
        ShiftPreset::create([
            'name' => 'Dag',
            'short_name' => 'D',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'color' => '#3B82F6',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ShiftPreset::create([
            'name' => 'Nacht',
            'short_name' => 'N',
            'start_time' => '23:00',
            'end_time' => '07:00',
            'color' => '#7C3AED',
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }
}
