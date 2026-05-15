<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epf_etf_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('epf_employee_rate', 5, 2)->default(8.00);   // employee contribution %
            $table->decimal('epf_employer_rate', 5, 2)->default(12.00);  // employer contribution %
            $table->decimal('etf_employer_rate', 5, 2)->default(3.00);   // employer ETF %
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // Add EPF/ETF breakdown columns to salary_payments
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->decimal('gross_salary',  12, 2)->default(0)->after('basic_salary');
            $table->decimal('epf_employee',  12, 2)->default(0)->after('deductions');
            $table->decimal('epf_employer',  12, 2)->default(0)->after('epf_employee');
            $table->decimal('etf_employer',  12, 2)->default(0)->after('epf_employer');
            $table->foreignId('epf_etf_setting_id')->nullable()->constrained('epf_etf_settings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropForeign(['epf_etf_setting_id']);
            $table->dropColumn(['gross_salary', 'epf_employee', 'epf_employer', 'etf_employer', 'epf_etf_setting_id']);
        });
        Schema::dropIfExists('epf_etf_settings');
    }
};
