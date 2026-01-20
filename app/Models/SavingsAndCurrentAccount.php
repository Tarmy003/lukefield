<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsAndCurrentAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customerid',
        'old_acct_no',
        'branch',
        'product_code',
        'bk_balance_corrected',
        'accrued_till_date',
        'last_accrued_date',
        'lien_status',
        'account_status',
        'account_tier',
        'date_opened',
    ];

    protected $casts = [
        'bk_balance_corrected' => 'decimal:2',
        'accrued_till_date' => 'decimal:2',
        'last_accrued_date' => 'date',
        'date_opened' => 'datetime',
        'account_status' => 'integer',
        'account_tier' => 'integer',
    ];

    /**
     * Get transaction history for this account
     */
    public function transactionHistory()
    {
        return $this->hasMany(NominalAccountsTrxPostingHistory::class, 'old_acct_no', 'old_acct_no')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get account status label
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            1 => 'Active',
            2 => 'Closed',
            3 => 'Dormant',
            4 => 'Frozen',
        ];

        return $statuses[$this->account_status] ?? 'Unknown';
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('account_status', 1);
    }

    /**
     * Search accounts
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('old_acct_no', 'like', "%{$search}%")
                ->orWhere('customerid', 'like', "%{$search}%")
                ->orWhere('branch', 'like', "%{$search}%");
        });
    }
}