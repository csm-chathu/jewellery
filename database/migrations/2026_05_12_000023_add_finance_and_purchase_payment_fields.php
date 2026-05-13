<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'credit'])->default('cash')->after('status');
            $table->string('cheque_number', 50)->nullable()->after('payment_method');
            $table->date('cheque_date')->nullable()->after('cheque_number');
            $table->string('cheque_bank_name', 100)->nullable()->after('cheque_date');
        });

        Schema::create('business_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->string('lender_name');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->decimal('monthly_installment', 14, 2)->nullable();
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->foreignId('liability_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('received_to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('loan_id')->constrained('business_loans')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('principal_amount', 14, 2)->default(0);
            $table->decimal('interest_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->foreignId('paid_from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('interest_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rent_payments', function (Blueprint $table) {
            $table->id();
            $table->string('rent_number')->unique();
            $table->string('month', 7);
            $table->string('property_name');
            $table->string('landlord_name')->nullable();
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])->default('bank_transfer');
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_bank_name', 100)->nullable();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('paid_from_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedTinyInteger('reminder_days_before')->default(5);
            $table->timestamp('last_reminded_at')->nullable();
            $table->enum('status', ['due', 'paid', 'overdue'])->default('due');
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['due_date', 'status']);
            $table->index(['month', 'property_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_payments');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('business_loans');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'cheque_number', 'cheque_date', 'cheque_bank_name']);
        });
    }
};
