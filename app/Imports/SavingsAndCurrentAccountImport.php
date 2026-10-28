<?php

namespace App\Imports;

use App\Models\SavingsAndCurrentAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

/**
 * Web import for the account master.
 *
 * Changes from the previous version:
 *  - WithChunkReading removed. With an .xlsx file, every chunk re-reads the
 *    workbook from the start, so a sheet whose used range runs to row 1,048,576
 *    (common after Excel formats whole columns) is read thousands of times.
 *    The account master is a few thousand rows and fits in memory easily.
 *  - SkipsEmptyRows added so blank rows at the bottom of a sheet are ignored.
 *  - Validation no longer requires 'string': Excel hands numeric cells over as
 *    numbers, so an account number or customer ID stored as a number failed
 *    validation even though it was present.
 *  - customerid and account number are stored as text.
 *
 * For a full reload, use `php artisan archive:import accounts <file> --replace`.
 */
class SavingsAndCurrentAccountImport implements ToModel, WithHeadingRow, WithBatchInserts, SkipsEmptyRows, WithValidation
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new SavingsAndCurrentAccount([
            'customerid' => $this->text($row['customerid'] ?? null),
            'old_acct_no' => $this->text($row['oldacctno'] ?? null),
            'branch' => $this->text($row['branch'] ?? null),
            'product_code' => $this->text($row['productcode'] ?? null),
            'bk_balance_corrected' => $this->parseDecimal($row['bkbalancecorrected'] ?? 0) ?? 0,
            'accrued_till_date' => $this->parseDecimal($row['accruedtilldate'] ?? null),
            'last_accrued_date' => $this->parseDate($row['lastaccrueddate'] ?? null),
            'lien_status' => $row['lienstatus'] ?? null,
            'account_status' => $row['accountstatus'] ?? 1,
            'account_tier' => $row['accounttier'] ?? 1,
            'date_opened' => $this->parseDate($row['dateopened'] ?? null),
        ]);
    }

    private function text($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return ($value === '' || strtoupper($value) === 'NULL') ? null : $value;
    }

    /**
     * Returns 'Y-m-d H:i:s' or null. Excel serial numbers, placeholders such as
     * 01/00/00, and dates outside 1950-2150 become null.
     */
    private function parseDate($value): ?string
    {
        $value = $this->text($value);
        if ($value === null) {
            return null;
        }

        try {
            if (preg_match('#^(\d{1,2}/0{1,2}/\d{1,4}|0{1,2}/\d{1,2}/\d{1,4})$#', $value)) {
                return null;
            }

            if (is_numeric($value)) {
                $serial = (int) floor((float) $value);
                if ($serial < 1) {
                    return null;
                }
                $date = Carbon::create(1899, 12, 30, 0, 0, 0)->addDays($serial);
            } else {
                $date = Carbon::parse($value);
            }
        } catch (\Throwable $e) {
            return null;
        }

        if ($date->year < 1950 || $date->year > 2150) {
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Parse decimal values from various formats. Returns an exact string.
     */
    private function parseDecimal($value): ?string
    {
        $value = $this->text($value);
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[^\d.-]/', '', $value);

        return preg_match('/^-?\d+(\.\d+)?$/', $value) ? $value : null;
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'oldacctno' => 'required',
            'customerid' => 'required',
        ];
    }
}
