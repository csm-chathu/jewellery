<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('informal_gold_purchases', function (Blueprint $table) {
            $table->string('weight_photo_url')->nullable()->after('invoice_photo_url');
        });
    }

    public function down(): void
    {
        Schema::table('informal_gold_purchases', function (Blueprint $table) {
            $table->dropColumn('weight_photo_url');
        });
    }
};
