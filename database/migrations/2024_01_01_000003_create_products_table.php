<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('material')->nullable();  // gold, silver, platinum, etc.
            $table->decimal('weight', 8, 3)->nullable();  // in grams
            $table->string('karat')->nullable();  // 18k, 22k, 24k, etc.
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('gemstone')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
