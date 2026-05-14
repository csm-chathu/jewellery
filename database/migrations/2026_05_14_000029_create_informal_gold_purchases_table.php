<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informal_gold_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
            $table->date('purchase_date');
            $table->string('description')->nullable();
            $table->enum('item_type', ['jewelry', 'coin', 'bar', 'scrap', 'other'])->default('jewelry');
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('deduction_weight', 10, 3)->default(0);
            $table->decimal('net_weight', 10, 3)->default(0);
            $table->string('declared_karat', 10)->default('unknown');
            $table->decimal('rate_per_gram', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer'])->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informal_gold_purchases');
    }
};
