<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaratSeeder extends Seeder
{
    public function run(): void
    {
        $carats = [
            ['label' => '24K', 'purity' => 1.0000, 'sort_order' => 1],
            ['label' => '22K', 'purity' => 0.9167, 'sort_order' => 2],
            ['label' => '18K', 'purity' => 0.7500, 'sort_order' => 3],
        ];

        foreach ($carats as $carat) {
            DB::table('carats')->insertOrIgnore(array_merge($carat, [
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
