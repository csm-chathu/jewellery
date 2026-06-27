<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sl_article_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('article');
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('gold_rate', 12, 2)->default(280000);
            $table->decimal('by_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('profit', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sl_article_sales');
    }
};
