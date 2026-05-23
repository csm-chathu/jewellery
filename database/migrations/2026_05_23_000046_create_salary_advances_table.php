<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->string('advance_number')->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // given by
            $table->decimal('amount', 12, 2);
            $table->date('given_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'deducted', 'cancelled'])->default('pending');
            $table->foreignId('salary_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });

        // Track how much advance is recovered in each salary payment
        if (!Schema::hasColumn('salary_payments', 'advance_deduction')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $table->decimal('advance_deduction', 12, 2)->default(0)->after('deductions');
            });
        }

        // Seed account 1300 — Salary Advance (current asset)
        if (!DB::table('accounts')->where('code', '1300')->exists()) {
            DB::table('accounts')->insert([
                'code'        => '1300',
                'name'        => 'Salary Advance',
                'type'        => 'asset',
                'sub_type'    => 'current_asset',
                'is_active'   => true,
                'is_system'   => true,
                'description' => 'Outstanding salary advances given to employees',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advances');

        if (Schema::hasColumn('salary_payments', 'advance_deduction')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $table->dropColumn('advance_deduction');
            });
        }

        DB::table('accounts')->where('code', '1300')->where('is_system', true)->delete();
    }
};
