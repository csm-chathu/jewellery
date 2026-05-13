<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_public_id')->nullable()->after('image');
        });

        Schema::table('gold_buybacks', function (Blueprint $table) {
            $table->json('proof_images')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_public_id');
        });

        Schema::table('gold_buybacks', function (Blueprint $table) {
            $table->dropColumn('proof_images');
        });
    }
};
