<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_account_id')->nullable()->after('category')
                  ->constrained('accounts')->nullOnDelete();
            $table->foreignId('paid_from_account_id')->nullable()->after('expense_account_id')
                  ->constrained('accounts')->nullOnDelete();

            // Make category nullable — new expenses use expense_account_id instead
            $table->string('category', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropForeign(['paid_from_account_id']);
            $table->dropColumn(['expense_account_id', 'paid_from_account_id']);
            $table->string('category', 100)->nullable(false)->change();
        });
    }
};
