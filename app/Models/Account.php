<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'sub_type', 'parent_id',
        'branch_id', 'is_active', 'is_system', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /** Normal balance direction: debit for assets/expenses, credit for liabilities/equity/revenue */
    public function normalBalance(): string
    {
        return in_array($this->type, ['asset', 'expense']) ? 'debit' : 'credit';
    }
}
