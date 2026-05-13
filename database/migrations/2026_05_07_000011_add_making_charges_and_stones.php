<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Making charges
            $table->enum('making_charge_type', ['per_gram', 'per_piece', 'percentage'])->default('per_gram')->after('karat');
            $table->decimal('making_charge', 10, 2)->default(0)->after('making_charge_type');
            $table->decimal('wastage_percent', 5, 2)->default(0)->after('making_charge');
            // Gemstone fields
            $table->decimal('gemstone_weight', 8, 3)->nullable()->after('wastage_percent');
            $table->decimal('gemstone_value', 12, 2)->default(0)->after('gemstone_weight');
            $table->string('gemstone_quality')->nullable()->after('gemstone_value'); // e.g. "VS1", "SI2"
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('gold_value', 12, 2)->default(0)->after('total');
            $table->decimal('gemstone_value', 12, 2)->default(0)->after('gold_value');
            $table->decimal('making_charge', 12, 2)->default(0)->after('gemstone_value');
            $table->decimal('wastage_amount', 12, 2)->default(0)->after('making_charge');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('gold_value_total', 14, 2)->default(0)->after('tax');
            $table->decimal('gemstone_value_total', 14, 2)->default(0)->after('gold_value_total');
            $table->decimal('making_charges_total', 14, 2)->default(0)->after('gemstone_value_total');
            $table->decimal('wastage_total', 14, 2)->default(0)->after('making_charges_total');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('wastage_total'); // applied tax %
            $table->string('sold_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['making_charge_type','making_charge','wastage_percent','gemstone_weight','gemstone_value','gemstone_quality']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['gold_value','gemstone_value','making_charge','wastage_amount']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['gold_value_total','gemstone_value_total','making_charges_total','wastage_total','tax_rate']);
        });
    }
};
