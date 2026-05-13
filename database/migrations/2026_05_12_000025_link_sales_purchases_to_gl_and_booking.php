<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('sale_type', ['instant', 'booking'])->default('instant')->after('payment_status');
            $table->enum('delivery_status', ['booked', 'delivered', 'cancelled'])->default('delivered')->after('sale_type');
            $table->date('booking_expires_at')->nullable()->after('delivery_status');
            $table->timestamp('delivered_at')->nullable()->after('booking_expires_at');
            $table->foreignId('journal_entry_id')->nullable()->after('notes')->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('notes')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn(['sale_type', 'delivery_status', 'booking_expires_at', 'delivered_at']);
        });
    }
};
