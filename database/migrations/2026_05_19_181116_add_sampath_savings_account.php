<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('accounts')->where('code', '1020')->exists()) {
            DB::table('accounts')->insert([
                'code'       => '1020',
                'name'       => 'Sampath Bank – Savings',
                'type'       => 'asset',
                'sub_type'   => 'current_asset',
                'is_system'  => false,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->where('code', '1020')->delete();
    }
};
