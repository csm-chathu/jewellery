<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('view_token', 40)->nullable()->unique()->after('notes');
        });

        // Back-fill existing rows
        DB::table('sales')->whereNull('view_token')->orderBy('id')->each(function ($row) {
            DB::table('sales')->where('id', $row->id)->update(['view_token' => Str::random(32)]);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('view_token');
        });
    }
};
