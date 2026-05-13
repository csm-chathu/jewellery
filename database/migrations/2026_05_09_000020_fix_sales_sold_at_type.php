<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill missing/invalid sold_at values first.
        DB::statement("UPDATE sales SET sold_at = created_at WHERE sold_at IS NULL OR sold_at = '' OR sold_at = '0' OR sold_at = '0000-00-00 00:00:00'");

        // Convert sold_at back to datetime for proper filtering/sorting.
        DB::statement('ALTER TABLE sales MODIFY sold_at DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales MODIFY sold_at VARCHAR(255) NULL');
    }
};
