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
        Schema::table('business_loans', function (Blueprint $table) {
            $table->date('next_payment_date')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('business_loans', function (Blueprint $table) {
            $table->dropColumn('next_payment_date');
        });
    }
};
