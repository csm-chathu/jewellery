<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_gold_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 30)->unique();
            $table->string('borrower_name', 150);
            $table->string('borrower_nic', 20)->nullable();
            $table->string('borrower_phone', 20)->nullable();
            $table->text('pledge_description')->nullable();
            $table->string('declared_karat', 10)->nullable();
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('net_weight', 10, 3)->default(0);
            $table->decimal('loan_amount', 12, 2);
            $table->decimal('outstanding_principal', 12, 2);
            $table->decimal('interest_rate_monthly', 5, 2)->default(0);
            $table->date('disbursed_date');
            $table->date('maturity_date')->nullable();
            $table->enum('status', ['active', 'closed', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
        });

        Schema::create('private_gold_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_gold_loan_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_gold_loan_repayments');
        Schema::dropIfExists('private_gold_loans');
    }
};
