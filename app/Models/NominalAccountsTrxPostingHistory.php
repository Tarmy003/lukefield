<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NominalAccountsTrxPostingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customerid',
        'old_acct_no',
        'account_id',
        'transaction_id',
        'transaction_order',
        'transaction_date',
        'financial_date',
        'reference_id',
        'narration',
        'debit',
        'credit',
        'opening_balance',
        'balance_after',
        'entry_code',
        'record_type',
        'posting_type',
        'instrument_no',
        'is_reversed',
        'reversal_reference_no',
        'product_code',
        'branch',
        'account_status',
        'date_opened',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'financial_date' => 'datetime',
        'date_opened' => 'datetime',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'opening_balance' => 'decimal:5',
        'balance_after' => 'decimal:5',
        'is_reversed' => 'boolean',
        'record_type' => 'integer',
        'transaction_order' => 'integer',
    ];

    /**
     * Get the account this transaction belongs to
     */
    public function account()
    {
        return $this->belongsTo(SavingsAndCurrentAccount::class, 'old_acct_no', 'old_acct_no');
    }

    /**
     * Get transaction type label
     */
    public function getTransactionTypeAttribute()
    {
        return $this->record_type == 1 ? 'Debit' : 'Credit';
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope for account
     */
    public function scopeForAccount($query, $accountNumber)
    {
        return $query->where('old_acct_no', $accountNumber);
    }

    /**
     * Scope for non-reversed transactions
     */
    public function scopeActive($query)
    {
        return $query->where('is_reversed', false);
    }

    /**
     * Scope for debits only
     */
    public function scopeDebits($query)
    {
        return $query->where('record_type', 1);
    }

    /**
     * Scope for credits only
     */
    public function scopeCredits($query)
    {
        return $query->where('record_type', 2);
    }
}