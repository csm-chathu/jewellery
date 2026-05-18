<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_made_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable(); // freetext fallback

            $table->string('description');
            $table->string('drawing_image_url')->nullable();

            // Estimated at order creation
            $table->decimal('estimated_weight', 10, 3)->default(0);
            $table->string('karat', 10)->nullable();
            $table->decimal('gold_rate_per_gram', 12, 2)->default(0);
            $table->decimal('estimated_gold_cost', 12, 2)->default(0);
            $table->decimal('making_charge', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('estimated_total', 12, 2)->default(0);

            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->date('advance_paid_at')->nullable();

            // Filled when completed
            $table->decimal('final_weight', 10, 3)->nullable();
            $table->decimal('final_gold_cost', 12, 2)->nullable();
            $table->decimal('final_making_charge', 12, 2)->nullable();
            $table->decimal('final_other_charges', 12, 2)->nullable();
            $table->decimal('final_total', 12, 2)->nullable();
            $table->decimal('balance_amount', 12, 2)->nullable();

            // Status: pending → in_progress → completed → issued
            $table->enum('status', ['pending', 'in_progress', 'completed', 'issued'])->default('pending');

            $table->date('expected_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->date('issued_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_made_orders');
    }
};
