<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('sub_type', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['entry_date', 'status']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Seed default Chart of Accounts for a jewellery business ──
        $now = now();
        $accounts = [
            // ASSETS (1xxx)
            ['code' => '1000', 'name' => 'Cash on Hand',             'type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => true],
            ['code' => '1010', 'name' => 'Bank Account',              'type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable',       'type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => true],
            ['code' => '1200', 'name' => 'Inventory – Finished Goods','type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => true],
            ['code' => '1210', 'name' => 'Inventory – Gold Stock',    'type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => true],
            ['code' => '1220', 'name' => 'Inventory – Scrap Gold',    'type' => 'asset',     'sub_type' => 'current_asset',        'is_system' => false],
            ['code' => '1500', 'name' => 'Equipment & Fixtures',      'type' => 'asset',     'sub_type' => 'fixed_asset',          'is_system' => false],
            ['code' => '1510', 'name' => 'Accumulated Depreciation',  'type' => 'asset',     'sub_type' => 'fixed_asset',          'is_system' => false],
            // LIABILITIES (2xxx)
            ['code' => '2000', 'name' => 'Accounts Payable',          'type' => 'liability', 'sub_type' => 'current_liability',    'is_system' => true],
            ['code' => '2100', 'name' => 'VAT / Tax Payable',         'type' => 'liability', 'sub_type' => 'current_liability',    'is_system' => true],
            ['code' => '2200', 'name' => 'Customer Deposits',         'type' => 'liability', 'sub_type' => 'current_liability',    'is_system' => false],
            ['code' => '2500', 'name' => 'Long-term Loans',           'type' => 'liability', 'sub_type' => 'long_term_liability',  'is_system' => false],
            // EQUITY (3xxx)
            ['code' => '3000', 'name' => "Owner's Capital",           'type' => 'equity',    'sub_type' => 'equity',               'is_system' => true],
            ['code' => '3100', 'name' => 'Retained Earnings',         'type' => 'equity',    'sub_type' => 'equity',               'is_system' => true],
            // REVENUE (4xxx)
            ['code' => '4000', 'name' => 'Jewellery Sales Revenue',   'type' => 'revenue',   'sub_type' => 'operating_revenue',    'is_system' => true],
            ['code' => '4100', 'name' => 'Gold Buy-Back Revenue',     'type' => 'revenue',   'sub_type' => 'operating_revenue',    'is_system' => false],
            ['code' => '4200', 'name' => 'Making Charge Revenue',     'type' => 'revenue',   'sub_type' => 'operating_revenue',    'is_system' => false],
            ['code' => '4900', 'name' => 'Other Income',              'type' => 'revenue',   'sub_type' => 'other_income',         'is_system' => false],
            // EXPENSES (5xxx)
            ['code' => '5000', 'name' => 'Cost of Goods Sold',        'type' => 'expense',   'sub_type' => 'cogs',                 'is_system' => true],
            ['code' => '5100', 'name' => 'Gold Purchase Cost',        'type' => 'expense',   'sub_type' => 'cogs',                 'is_system' => false],
            ['code' => '5200', 'name' => 'Rent Expense',              'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
            ['code' => '5210', 'name' => 'Salaries & Wages',          'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
            ['code' => '5220', 'name' => 'Utilities Expense',         'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
            ['code' => '5230', 'name' => 'Marketing & Advertising',   'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
            ['code' => '5300', 'name' => 'Tax Expense',               'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
            ['code' => '5900', 'name' => 'Miscellaneous Expense',     'type' => 'expense',   'sub_type' => 'operating',            'is_system' => false],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->insert(array_merge($account, [
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
