<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_balance_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('description')->nullable();
            $table->string('karat')->nullable();
            $table->decimal('give_weight_1', 10, 3)->nullable();
            $table->decimal('give_weight_2', 10, 3)->nullable();
            $table->decimal('give_weight_3', 10, 3)->nullable();
            $table->decimal('give_weight_4', 10, 3)->nullable();
            $table->string('article')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('wastage', 10, 3)->nullable();
            $table->decimal('other_charge', 12, 2)->nullable();
            $table->decimal('total_gold', 10, 3)->default(0);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_balance_entries');
    }
};
