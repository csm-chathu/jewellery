<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_articles', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->nullable();
            $table->date('received_date');
            $table->date('give_date')->nullable();
            $table->string('article');
            $table->text('damage')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('telephone')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('add_weight', 10, 3)->nullable();
            $table->decimal('advance', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('done')->default(false);
            $table->boolean('given')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_articles');
    }
};
