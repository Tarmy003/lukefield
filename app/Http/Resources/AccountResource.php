<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerid,
            'account_number' => $this->old_acct_no,
            'branch' => $this->branch,
            'product_code' => $this->product_code,
            'balance' => number_format($this->bk_balance_corrected, 2),
            'balance_raw' => (float) $this->bk_balance_corrected,
            'status' => $this->status_label,
            'status_code' => $this->account_status,
            'tier' => $this->account_tier,
            'date_opened' => $this->date_opened?->format('Y-m-d'),
            'lien_status' => $this->lien_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'transaction_count' => $this->when(isset($this->transaction_count), $this->transaction_count),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactionHistory')),
        ];
    }
}