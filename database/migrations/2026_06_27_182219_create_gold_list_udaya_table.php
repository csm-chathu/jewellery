<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_list_udaya', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->string('description')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('stock_weight', 10, 3)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('rate', 14, 4)->nullable();
            $table->string('average_karat')->nullable();
            $table->decimal('moose_pay', 14, 2)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_list_udaya');
    }
};
