<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
            $table->date('expense_date');
            $table->enum('category', ['transport', 'fees', 'commission', 'testing', 'tools', 'misc'])->default('misc');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer'])->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_expenses');
    }
};
