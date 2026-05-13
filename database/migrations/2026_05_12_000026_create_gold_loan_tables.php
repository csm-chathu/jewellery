<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('pledge_description');
            $table->enum('item_type', ['jewelry', 'coin', 'bar', 'scrap', 'other'])->default('jewelry');
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('deduction_weight', 10, 3)->default(0);
            $table->decimal('net_weight', 10, 3)->default(0);
            $table->enum('declared_karat', ['9k', '14k', '18k', '22k', '24k', 'unknown'])->default('unknown');
            $table->decimal('loan_amount', 14, 2);
            $table->decimal('interest_rate_monthly', 8, 4)->default(0);
            $table->date('disbursed_date');
            $table->date('maturity_date');
            $table->decimal('outstanding_principal', 14, 2)->default(0);
            $table->date('last_interest_date')->nullable();
            $table->foreignId('loan_receivable_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('disbursed_from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->enum('status', ['active', 'closed', 'overdue', 'forfeited'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'maturity_date']);
        });

        Schema::create('gold_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('gold_loan_id')->constrained('gold_loans')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('interest_component', 14, 2)->default(0);
            $table->decimal('principal_component', 14, 2)->default(0);
            $table->foreignId('received_to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('interest_income_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_date']);
        });

        $now = now();

        $existsReceivable = DB::table('accounts')->where('code', '1110')->exists();
        if (!$existsReceivable) {
            DB::table('accounts')->insert([
                'code' => '1110',
                'name' => 'Gold Loan Receivable',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'is_active' => true,
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existsInterestIncome = DB::table('accounts')->where('code', '4300')->exists();
        if (!$existsInterestIncome) {
            DB::table('accounts')->insert([
                'code' => '4300',
                'name' => 'Loan Interest Income',
                'type' => 'revenue',
                'sub_type' => 'other_income',
                'is_active' => true,
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_loan_repayments');
        Schema::dropIfExists('gold_loans');
    }
};
