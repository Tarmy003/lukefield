<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'full_name' => $this->full_name,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'surname' => $this->surname,
            'other_name' => $this->other_name,
            'customer_type' => $this->customer_type,
            'sex' => $this->sex,
            'dob' => $this->dob?->format('Y-m-d'),
            'email' => $this->email,
            'mobile_phone' => $this->mobile_phone,
            'business_phone_no' => $this->business_phone_no,
            'residential_address' => $this->residential_address,
            'residential_state' => $this->residential_state,
            'residential_town' => $this->residential_town,
            'office_name' => $this->office_name,
            'office_address' => $this->office_address,
            'acct_officer' => $this->acct_officer,
            'occupation' => $this->occupation,
            'status' => $this->status,
            'bvn' => $this->bvn,
            'nin' => $this->nin,
            'sector' => $this->sector,
            'pep' => $this->pep,
            'date_opened' => $this->date_opened?->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'accounts_count' => $this->when(isset($this->accounts_count), $this->accounts_count),
            'accounts' => AccountResource::collection($this->whenLoaded('accounts')),
        ];
    }
}