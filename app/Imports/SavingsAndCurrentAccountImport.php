<?php

namespace App\Imports;

use App\Models\SavingsAndCurrentAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class SavingsAndCurrentAccountImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, WithValidation
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Handle date parsing
        $dateOpened = null;
        if (!empty($row['dateopened'])) {
            try {
                $dateOpened = Carbon::parse($row['dateopened']);
            } catch (\Exception $e) {
                $dateOpened = null;
            }
        }

        $lastAccruedDate = null;
        if (!empty($row['lastaccrueddate'])) {
            try {
                $lastAccruedDate = Carbon::parse($row['lastaccrueddate']);
            } catch (\Exception $e) {
                $lastAccruedDate = null;
            }
        }

        return new SavingsAndCurrentAccount([
            'customerid' => $row['customerid'] ?? null,
            'old_acct_no' => $row['oldacctno'] ?? null,
            'branch' => $row['branch'] ?? null,
            'product_code' => $row['productcode'] ?? null,
            'bk_balance_corrected' => $this->parseDecimal($row['bkbalancecorrected'] ?? 0),
            'accrued_till_date' => $this->parseDecimal($row['accruedtilldate'] ?? null),
            'last_accrued_date' => $lastAccruedDate,
            'lien_status' => $row['lienstatus'] ?? null,
            'account_status' => $row['accountstatus'] ?? 1,
            'account_tier' => $row['accounttier'] ?? 1,
            'date_opened' => $dateOpened,
        ]);
    }

    /**
     * Parse decimal values from various formats
     */
    private function parseDecimal($value)
    {
        if (is_null($value) || $value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        // Remove currency symbols and spaces
        $value = preg_replace('/[^\d.-]/', '', $value);
        
        return $value !== '' ? (float)$value : null;
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Chunk size for reading
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'oldacctno' => 'required|string',
            'customerid' => 'required|string',
        ];
    }
}