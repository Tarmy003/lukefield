<?php

namespace App\Imports;

use App\Models\NominalAccountsTrxPostingHistory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class NominalAccountsTrxPostingHistoryImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $rowCount = 0;
    private $insertedCount = 0;
    private $duplicateCount = 0;
    private $errorCount = 0;
    private $skipCount = 0;
    private $invalidDatesCount = 0;
    private $startTime;
    private $lastLogTime;
    private $chunkNumber = 0;
    
    private $batchData = [];
    private $batchSize = 1000; // Increased for better performance

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->lastLogTime = $this->startTime;
        $this->logProgress("=== NEW IMPORT SESSION STARTED ===");
        $this->logProgress("Initial record count: " . NominalAccountsTrxPostingHistory::count());
    }

    private function logProgress($message, $level = 'info')
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $elapsed = round(microtime(true) - $this->startTime, 2);
        $memory = round(memory_get_usage(true) / 1024 / 1024, 2);
        
        $logMessage = "[{$timestamp}] [{$elapsed}s] [Memory: {$memory}MB] {$message}";
        
        Storage::disk('local')->append('logs/import-transactions.log', $logMessage);
        Log::channel('single')->{$level}($logMessage);
    }

    public function model(array $row)
    {
        $this->rowCount++;
        
        // Log progress every 500 rows
        if ($this->rowCount % 500 == 0) {
            $currentTime = microtime(true);
            $timeSinceLastLog = round($currentTime - $this->lastLogTime, 2);
            $rate = $timeSinceLastLog > 0 ? round(500 / $timeSinceLastLog, 2) : 0;
            
            $this->logProgress(
                "Progress: {$this->rowCount} rows | " .
                "Inserted: {$this->insertedCount} | " .
                "Pending: " . count($this->batchData) . " | " .
                "Duplicates: {$this->duplicateCount} | " .
                "Skipped: {$this->skipCount} | " .
                "Errors: {$this->errorCount} | " .
                "Rate: {$rate} rows/sec"
            );
            
            $this->lastLogTime = $currentTime;
        }

        try {
            // Skip if essential fields are missing
            if (empty($row['oldacctno']) || empty($row['transactionid'])) {
                $this->skipCount++;
                return null;
            }

            // Parse dates
            $transactionDate = $this->parseDate($row['transactiondate'] ?? null);
            $financialDate = $this->parseDate($row['financialdate'] ?? null);
            $dateOpened = $this->parseDate($row['dateopened'] ?? null);

            // Prepare data
            $data = [
                'customerid' => $row['customerid'] ?? null,
                'old_acct_no' => trim($row['oldacctno']),
                'account_id' => $row['accountid'] ?? null,
                'transaction_id' => trim($row['transactionid']),
                'transaction_order' => !empty($row['transactionorder']) ? (int)$row['transactionorder'] : null, // NEW
                'transaction_date' => $transactionDate,
                'financial_date' => $financialDate,
                'reference_id' => $row['referenceid'] ?? null,
                'narration' => $row['narration'] ?? null,
                'debit' => $this->parseDecimal($row['debit'] ?? 0),
                'credit' => $this->parseDecimal($row['credit'] ?? 0),
                'opening_balance' => $this->parseDecimal($row['openingbalance'] ?? 0),
                'balance_after' => $this->parseDecimal($row['balanceafter'] ?? 0),
                'entry_code' => $row['entrycode'] ?? null,
                'record_type' => $row['recordtype'] ?? null,
                'posting_type' => $row['postingtype'] ?? null,
                'instrument_no' => $row['instrumentno'] ?? null,
                'is_reversed' => $this->parseBoolean($row['isreversed'] ?? 0),
                'reversal_reference_no' => $row['reversalreferenceno'] ?? null,
                'product_code' => $row['productcode'] ?? null,
                'branch' => $row['branch'] ?? null,
                'account_status' => $row['accountstatus'] ?? null,
                'date_opened' => $dateOpened,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->batchData[] = $data;
            
            // Insert when batch is full
            if (count($this->batchData) >= $this->batchSize) {
                $this->insertBatch();
            }

            return null;

        } catch (\Exception $e) {
            $this->errorCount++;
            
            if ($this->errorCount <= 10) {
                $this->logProgress(
                    "Row {$this->rowCount} Error: " . $e->getMessage() . 
                    " | Data: " . json_encode(array_slice($row, 0, 5)),
                    'error'
                );
            }
            
            return null;
        }
    }

    private function insertBatch()
    {
        if (empty($this->batchData)) {
            return;
        }

        $batchCount = count($this->batchData);
        
        try {
            // Use insertOrIgnore to skip duplicates automatically
            // This requires the unique constraint we added
            $inserted = DB::table('nominal_accounts_trx_posting_histories')
                ->insertOrIgnore($this->batchData);
            
            // Calculate how many were actually inserted vs duplicates
            $actualInserted = $inserted; // insertOrIgnore returns number of inserted rows
            $duplicatesInBatch = $batchCount - $actualInserted;
            
            $this->insertedCount += $actualInserted;
            $this->duplicateCount += $duplicatesInBatch;
            
            $this->logProgress(
                "✓ Batch processed: {$batchCount} records | " .
                "Inserted: {$actualInserted} | " .
                "Duplicates skipped: {$duplicatesInBatch} | " .
                "Total inserted: {$this->insertedCount}"
            );
            
        } catch (\Exception $e) {
            // If batch fails, try one by one
            $this->logProgress("Batch insert failed, trying individual inserts: " . $e->getMessage(), 'warning');
            
            $successInBatch = 0;
            $duplicatesInBatch = 0;
            
            foreach ($this->batchData as $index => $data) {
                try {
                    $result = DB::table('nominal_accounts_trx_posting_histories')
                        ->insertOrIgnore($data);
                    
                    if ($result) {
                        $successInBatch++;
                    } else {
                        $duplicatesInBatch++;
                    }
                    
                } catch (\Exception $individualError) {
                    $this->errorCount++;
                    
                    if ($this->errorCount <= 10) {
                        $this->logProgress(
                            "Individual insert failed for record {$index}: " . 
                            $individualError->getMessage(),
                            'warning'
                        );
                    }
                }
            }
            
            $this->insertedCount += $successInBatch;
            $this->duplicateCount += $duplicatesInBatch;
            
            $this->logProgress(
                "Individual inserts: {$successInBatch} successful, " .
                "{$duplicatesInBatch} duplicates, " .
                ($batchCount - $successInBatch - $duplicatesInBatch) . " errors"
            );
        }
        
        // Clear batch
        $this->batchData = [];
        
        // Force garbage collection every 10 batches
        if ($this->insertedCount % ($this->batchSize * 10) == 0) {
            gc_collect_cycles();
        }
    }

    public function __destruct()
    {
        // Insert remaining batch
        if (!empty($this->batchData)) {
            $this->logProgress("Processing final batch of " . count($this->batchData) . " records");
            $this->insertBatch();
        }
        
        $duration = round(microtime(true) - $this->startTime, 2);
        $finalCount = NominalAccountsTrxPostingHistory::count();
        
        $this->logProgress("=== IMPORT COMPLETED ===");
        $this->logProgress("Total rows processed: {$this->rowCount}");
        $this->logProgress("Records inserted: {$this->insertedCount}");
        $this->logProgress("Duplicates skipped: {$this->duplicateCount}");
        $this->logProgress("Rows skipped (missing data): {$this->skipCount}");
        $this->logProgress("Errors: {$this->errorCount}");
        $this->logProgress("Invalid dates: {$this->invalidDatesCount}");
        $this->logProgress("Duration: {$duration}s");
        $this->logProgress("Final DB count: {$finalCount}");
        $this->logProgress("========================");
    }

    public function chunkSize(): int
    {
        $this->chunkNumber++;
        if ($this->chunkNumber % 5 == 0) {
            $this->logProgress("Processing chunk #{$this->chunkNumber}");
        }
        return 1000;
    }

    public function batchSize(): int
    {
        return 1; // Disable Laravel's batch, we handle it manually
    }

    private function parseDate($value)
    {
        if (empty($value) || strtoupper($value) === 'NULL' || $value === 0 || $value === '0') {
            return null;
        }

        $valueStr = (string)$value;
        
        // Skip invalid dates like "01/00/00"
        if (preg_match('/^(\d{1,2})\/0{1,2}\/0{1,4}$/i', $valueStr) || 
            preg_match('/^0{1,2}\/0{1,2}\/\d{1,4}$/i', $valueStr)) {
            $this->invalidDatesCount++;
            return null;
        }

        try {
            $parsedDate = null;
            
            if (is_numeric($value)) {
                $numValue = (float)$value;
                
                if ($numValue <= 0 || $numValue < 1) {
                    $this->invalidDatesCount++;
                    return null;
                }
                
                try {
                    $parsedDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($numValue));
                } catch (\Exception $e) {
                    $this->invalidDatesCount++;
                    return null;
                }
            } else {
                try {
                    $parsedDate = Carbon::parse($valueStr);
                } catch (\Exception $e) {
                    $this->invalidDatesCount++;
                    return null;
                }
            }
            
            if ($parsedDate && ($parsedDate->year < 1950 || $parsedDate->year > 2150)) {
                $this->invalidDatesCount++;
                return null;
            }
            
            return $parsedDate ? $parsedDate->format('Y-m-d H:i:s') : null;
            
        } catch (\Exception $e) {
            $this->invalidDatesCount++;
            return null;
        }
    }

    private function parseDecimal($value)
    {
        if (is_null($value) || $value === '' || strtoupper($value) === 'NULL') {
            return 0;
        }

        $value = preg_replace('/[^\d.-]/', '', $value);
        return $value !== '' ? (float)$value : 0;
    }

    private function parseBoolean($value)
    {
        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'y'], true);
    }

    public function getStats()
    {
        $duration = round(microtime(true) - $this->startTime, 2);
        
        return [
            'total_rows' => $this->rowCount,
            'inserted' => $this->insertedCount,
            'duplicates_skipped' => $this->duplicateCount,
            'rows_skipped' => $this->skipCount,
            'errors' => $this->errorCount,
            'invalid_dates' => $this->invalidDatesCount,
            'duration' => $duration,
        ];
    }
}