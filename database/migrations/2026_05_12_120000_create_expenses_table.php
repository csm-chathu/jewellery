<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date')->default(now());
            $table->string('category', 100);
            $table->string('description', 500);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50)->default('cash'); // cash, cheque, bank_transfer, card
            $table->string('reference_number', 100)->nullable(); // cheque no, receipt no, etc.
            $table->foreignId('paid_by_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('expense_date');
            $table->index('category');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
