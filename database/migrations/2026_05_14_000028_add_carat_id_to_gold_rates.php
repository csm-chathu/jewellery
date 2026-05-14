<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_rates', function (Blueprint $table) {
            $table->foreignId('carat_id')->nullable()->after('date')->constrained('carats')->nullOnDelete();
        });

        // Drop old unique-on-date, add unique on (date, carat_id)
        Schema::table('gold_rates', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->unique(['date', 'carat_id']);
        });
    }

    public function down(): void
    {
        Schema::table('gold_rates', function (Blueprint $table) {
            $table->dropUnique(['date', 'carat_id']);
            $table->unique('date');
            $table->dropConstrainedForeignId('carat_id');
        });
    }
};
