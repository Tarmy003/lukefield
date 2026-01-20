<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'title',
        'first_name',
        'surname',
        'other_name',
        'customer_type',
        'sex',
        'state_of_origin',
        'dob',
        'residential_address',
        'residential_state',
        'residential_town',
        'email',
        'mobile_phone',
        'business_phone_no',
        'office_name',
        'office_address',
        'office_phone',
        'acct_officer',
        'occupation',
        'edu_level',
        'status',
        'date_opened',
        'bvn',
        'nin',
        'tin',
        'id_type',
        'id_no',
        'id_issue_date',
        'id_expiry_date',
        'marital_status',
        'next_of_kin',
        'next_of_kin_phone_no',
        'next_of_kin_address',
        'reg_no',
        'sector',
        'pep',
        'pep_details',
    ];

    protected $casts = [
        'dob' => 'date',
        'date_opened' => 'date',
        'id_issue_date' => 'date',
        'id_expiry_date' => 'date',
    ];

    public function getFullNameAttribute()
    {
        return trim("{$this->title} {$this->first_name} {$this->surname} {$this->other_name}");
    }

    /**
     * Search customers
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('customer_id', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('surname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile_phone', 'like', "%{$search}%")
                ->orWhere('bvn', 'like', "%{$search}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopePep($query)
    {
        return $query->where('pep', 'YES');
    }

    /**
     * Get accounts for this customer
     */
    public function accounts()
    {
        $paddedCustomerId = str_pad($this->customer_id, 6, '0', STR_PAD_LEFT);
        return $this->hasMany(SavingsAndCurrentAccount::class, 'customerid', 'customerid')
            ->where('customerid', $paddedCustomerId);
    }
}