<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informal_gold_purchases', function (Blueprint $table) {
            $table->string('nic_front_url')->nullable()->after('notes');
            $table->string('nic_back_url')->nullable()->after('nic_front_url');
            $table->string('invoice_photo_url')->nullable()->after('nic_back_url');
        });
    }

    public function down(): void
    {
        Schema::table('informal_gold_purchases', function (Blueprint $table) {
            $table->dropColumn(['nic_front_url', 'nic_back_url', 'invoice_photo_url']);
        });
    }
};
