<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_date', 'category', 'description', 'amount', 'payment_method',
        'reference_number', 'paid_by_user_id', 'branch_id', 'notes', 'created_by_user_id',
        'expense_account_id', 'paid_from_account_id', 'journal_entry_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    const CATEGORIES = [
        'rent'             => 'Shop Rent',
        'utilities'        => 'Utilities (Electricity, Water)',
        'supplies'         => 'Office Supplies (Pen, Paper, etc)',
        'maintenance'      => 'Maintenance & Repairs',
        'travel'           => 'Travel & Transportation',
        'marketing'        => 'Marketing & Advertising',
        'insurance'        => 'Insurance',
        'licenses'         => 'Licenses & Permits',
        'professional'     => 'Professional Fees',
        'miscellaneous'    => 'Miscellaneous',
    ];

    const PAYMENT_METHODS = ['cash', 'cheque', 'bank_transfer', 'card'];

    // Map expense categories to GL account codes
    const CATEGORY_ACCOUNT_CODES = [
        'rent'             => '5100',
        'utilities'        => '5200',
        'supplies'         => '5300',
        'maintenance'      => '5400',
        'travel'           => '5500',
        'marketing'        => '5600',
        'insurance'        => '5700',
        'licenses'         => '5800',
        'professional'     => '5900',
        'miscellaneous'    => '5999',
    ];

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paidFromAccount()
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
