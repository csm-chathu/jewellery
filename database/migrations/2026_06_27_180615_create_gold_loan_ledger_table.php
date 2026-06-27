<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_loan_ledger', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->decimal('loan_rate', 12, 2)->nullable();
            $table->decimal('loan_amount', 14, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('description')->nullable();
            $table->decimal('give_weight', 10, 3)->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_loan_ledger');
    }
};
