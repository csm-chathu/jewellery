<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_loans', function (Blueprint $table) {
            $table->enum('source', ['bank', 'customer', 'owner'])->default('bank')->after('loan_number');
        });
    }

    public function down(): void
    {
        Schema::table('business_loans', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
