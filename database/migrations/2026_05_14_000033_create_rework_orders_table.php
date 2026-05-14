<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rework_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();

            // Source of gold
            $table->enum('source_type', ['buyback', 'scrap', 'manual'])->default('manual');
            $table->foreignId('buyback_id')->nullable()->constrained('gold_buybacks')->nullOnDelete();
            $table->foreignId('scrap_item_id')->nullable()->constrained('scrap_items')->nullOnDelete();

            // Job details
            $table->string('description');
            $table->string('goldsmith_name')->nullable();

            // Input gold
            $table->decimal('input_weight', 10, 3)->default(0);
            $table->string('input_karat', 10)->nullable();
            $table->decimal('input_gold_cost', 12, 2)->default(0);  // buy-back paid / scrap estimated value

            // Added gold
            $table->decimal('added_gold_weight', 10, 3)->default(0);
            $table->decimal('added_gold_cost', 12, 2)->default(0);

            // Making charge
            $table->decimal('making_charge', 12, 2)->default(0);
            $table->string('making_charge_notes')->nullable();

            // Total cost (computed on save)
            $table->decimal('total_cost', 12, 2)->default(0);

            // Output (filled on completion)
            $table->decimal('output_weight', 10, 3)->nullable();
            $table->string('output_karat', 10)->nullable();
            $table->foreignId('output_product_id')->nullable()->constrained('products')->nullOnDelete();

            // Dates
            $table->date('started_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->date('completed_at')->nullable();

            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rework_orders');
    }
};
