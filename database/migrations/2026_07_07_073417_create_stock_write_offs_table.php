<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_write_offs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->enum('reason', ['damaged', 'lost', 'stolen', 'other']);
            $table->text('notes')->nullable();
            $table->decimal('estimated_value', 14, 2);
            $table->unsignedBigInteger('debit_account_id');
            $table->unsignedBigInteger('credit_account_id');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('written_off_by');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('debit_account_id')->references('id')->on('accounts');
            $table->foreign('credit_account_id')->references('id')->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_write_offs');
    }
};
