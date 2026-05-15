<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layaways', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('item_description', 255);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('active');
            $table->date('booking_date');
            $table->date('expected_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('layaway_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layaway_id')->constrained('layaways');
            $table->string('receipt_number', 20)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'card'])->default('cash');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layaway_payments');
        Schema::dropIfExists('layaways');
    }
};
