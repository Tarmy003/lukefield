<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_number' => $this->old_acct_no,
            'transaction_id' => $this->transaction_id,
            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
            'reference_id' => $this->reference_id,
            'narration' => $this->narration,
            'debit' => number_format($this->debit, 2),
            'credit' => number_format($this->credit, 2),
            'debit_raw' => (float) $this->debit,
            'credit_raw' => (float) $this->credit,
            'opening_balance' => number_format($this->opening_balance, 2),
            'balance_after' => number_format($this->balance_after, 2),
            'opening_balance_raw' => (float) $this->opening_balance,
            'balance_after_raw' => (float) $this->balance_after,
            'transaction_type' => $this->transaction_type,
            'record_type' => $this->record_type,
            'is_reversed' => $this->is_reversed,
            'instrument_no' => $this->instrument_no,
            'branch' => $this->branch,
        ];
    }
}