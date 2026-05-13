<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('nic', 30)->nullable()->unique();          // National ID
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->date('joined_date');
            $table->date('terminated_date')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();   // paid by
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();

            $table->date('period_from');
            $table->date('period_to');
            $table->date('payment_date');

            $table->decimal('basic_salary',  12, 2)->default(0);
            $table->decimal('allowances',    12, 2)->default(0);
            $table->decimal('deductions',    12, 2)->default(0);
            $table->decimal('net_salary',    12, 2)->default(0);

            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])->default('bank_transfer');
            $table->foreignId('paid_from_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->enum('status', ['draft', 'paid'])->default('paid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'period_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('employees');
    }
};
