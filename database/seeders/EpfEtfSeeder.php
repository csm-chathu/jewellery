<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EpfEtfSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('epf_etf_settings')->exists()) {
            return;
        }

        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        // Sri Lanka statutory rates
        DB::table('epf_etf_settings')->insert([
            'epf_employee_rate' => 8.00,
            'epf_employer_rate' => 12.00,
            'etf_employer_rate' => 3.00,
            'effective_from'    => now()->startOfYear()->toDateString(),
            'is_active'         => true,
            'notes'             => 'Sri Lanka statutory EPF/ETF rates',
            'created_by'        => $admin->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
