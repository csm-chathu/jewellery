<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_loans', function (Blueprint $table) {
            $table->foreignId('liability_account_id')->nullable()->change();
            $table->foreignId('received_to_account_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('business_loans', function (Blueprint $table) {
            $table->foreignId('liability_account_id')->nullable(false)->change();
            $table->foreignId('received_to_account_id')->nullable(false)->change();
        });
    }
};
