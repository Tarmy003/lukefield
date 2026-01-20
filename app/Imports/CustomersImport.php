<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomersImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $rowCount = 0;
    private $insertedCount = 0;
    private $updatedCount = 0;
    private $duplicateCount = 0;
    private $errorCount = 0;
    private $skipCount = 0;
    private $invalidDatesCount = 0;
    private $startTime;
    private $lastLogTime;
    private $chunkNumber = 0;
    private $replaceData;
    
    private $batchData = [];
    private $batchSize = 500; // Optimal for customer data

    public function __construct($replaceData = false)
    {
        $this->replaceData = $replaceData;
        $this->startTime = microtime(true);
        $this->lastLogTime = $this->startTime;
        $this->logProgress("=== NEW CUSTOMER IMPORT SESSION STARTED ===");
        $this->logProgress("Mode: " . ($replaceData ? "REPLACE" : "APPEND"));
        $this->logProgress("Initial customer count: " . Customer::count());
    }

    private function logProgress($message, $level = 'info')
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $elapsed = round(microtime(true) - $this->startTime, 2);
        $memory = round(memory_get_usage(true) / 1024 / 1024, 2);
        
        $logMessage = "[{$timestamp}] [{$elapsed}s] [Memory: {$memory}MB] {$message}";
        
        Storage::disk('local')->append('logs/import-customers.log', $logMessage);
        Log::channel('single')->{$level}($logMessage);
    }

    public function model(array $row)
    {
        $this->rowCount++;
        
        // Log progress every 250 rows
        if ($this->rowCount % 250 == 0) {
            $currentTime = microtime(true);
            $timeSinceLastLog = round($currentTime - $this->lastLogTime, 2);
            $rate = $timeSinceLastLog > 0 ? round(250 / $timeSinceLastLog, 2) : 0;
            
            $this->logProgress(
                "Progress: {$this->rowCount} rows | " .
                "Inserted: {$this->insertedCount} | " .
                "Updated: {$this->updatedCount} | " .
                "Pending: " . count($this->batchData) . " | " .
                "Duplicates: {$this->duplicateCount} | " .
                "Skipped: {$this->skipCount} | " .
                "Errors: {$this->errorCount} | " .
                "Rate: {$rate} rows/sec"
            );
            
            $this->lastLogTime = $currentTime;
        }

        try {
            // Skip if CustomerID is missing
            if (empty($row['customerid'])) {
                $this->skipCount++;
                return null;
            }

            // Parse dates
            $dob = $this->parseDate($row['dob'] ?? null);
            $dateOpened = $this->parseDate($row['dateopened'] ?? null);
            $idIssueDate = $this->parseDate($row['idissuedate'] ?? null);
            $idExpiryDate = $this->parseDate($row['idexpirydate'] ?? null);

            // Clean and prepare data
            $data = [
                'customer_id' => trim($row['customerid']),
                'title' => $this->cleanValue($row['title'] ?? null),
                'first_name' => $this->cleanValue($row['firstname'] ?? ''),
                'surname' => $this->cleanValue($row['surname'] ?? ''),
                'other_name' => $this->cleanValue($row['othername'] ?? null),
                'customer_type' => $this->cleanValue($row['customertype'] ?? null),
                'sex' => $this->cleanValue($row['sex'] ?? null),
                'state_of_origin' => $this->cleanValue($row['stateoforigin'] ?? null),
                'dob' => $dob,
                'residential_address' => $this->cleanValue($row['residentialaddress'] ?? null),
                'residential_state' => $this->cleanValue($row['residentialstate'] ?? null),
                'residential_town' => $this->cleanValue($row['residentialtown'] ?? null),
                'email' => $this->cleanEmail($row['email'] ?? null),
                'mobile_phone' => $this->cleanPhone($row['mobilephone'] ?? null),
                'business_phone_no' => $this->cleanPhone($row['businessphoneno'] ?? null),
                'office_name' => $this->cleanValue($row['officename'] ?? null),
                'office_address' => $this->cleanValue($row['officeaddress'] ?? null),
                'office_phone' => $this->cleanPhone($row['officephone'] ?? null),
                'acct_officer' => $this->cleanValue($row['acctofficer'] ?? null),
                'occupation' => $this->cleanValue($row['occupation'] ?? null),
                'edu_level' => $this->cleanValue($row['edulevel'] ?? null),
                'status' => $this->cleanValue($row['status'] ?? 'Active'),
                'date_opened' => $dateOpened,
                'bvn' => $this->cleanValue($row['bvn'] ?? null),
                'nin' => $this->cleanValue($row['nin'] ?? null),
                'tin' => $this->cleanValue($row['tin'] ?? null),
                'id_type' => $this->cleanValue($row['idtype'] ?? null),
                'id_no' => $this->cleanValue($row['idno'] ?? null),
                'id_issue_date' => $idIssueDate,
                'id_expiry_date' => $idExpiryDate,
                'marital_status' => $this->cleanValue($row['maritalstatus'] ?? null),
                'next_of_kin' => $this->cleanValue($row['nextofkin'] ?? null),
                'next_of_kin_phone_no' => $this->cleanPhone($row['nextofkinphoneno'] ?? null),
                'next_of_kin_address' => $this->cleanValue($row['nextofkinaddress'] ?? null),
                'reg_no' => $this->cleanValue($row['regno'] ?? null),
                'sector' => $this->cleanValue($row['sector'] ?? null),
                'pep' => strtoupper($this->cleanValue($row['pep'] ?? 'NO')),
                'pep_details' => $this->cleanValue($row['pepdetails'] ?? null),
                'updated_at' => now(),
            ];

            // Add created_at for new records
            if (!$this->replaceData) {
                $data['created_at'] = now();
            }

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
                    " | CustomerID: " . ($row['customerid'] ?? 'N/A'),
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
            if ($this->replaceData) {
                // Update or insert mode
                $insertedInBatch = 0;
                $updatedInBatch = 0;
                
                foreach ($this->batchData as $data) {
                    $customerId = $data['customer_id'];
                    
                    $existing = DB::table('customers')
                        ->where('customer_id', $customerId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if ($existing) {
                        DB::table('customers')
                            ->where('customer_id', $customerId)
                            ->update($data);
                        $updatedInBatch++;
                    } else {
                        $data['created_at'] = now();
                        DB::table('customers')->insert($data);
                        $insertedInBatch++;
                    }
                }
                
                $this->insertedCount += $insertedInBatch;
                $this->updatedCount += $updatedInBatch;
                
                $this->logProgress(
                    "✓ Batch processed: {$batchCount} records | " .
                    "Inserted: {$insertedInBatch} | " .
                    "Updated: {$updatedInBatch}"
                );
                
            } else {
                // Insert only mode (skip duplicates)
                $inserted = DB::table('customers')->insertOrIgnore($this->batchData);
                
                $actualInserted = $inserted;
                $duplicatesInBatch = $batchCount - $actualInserted;
                
                $this->insertedCount += $actualInserted;
                $this->duplicateCount += $duplicatesInBatch;
                
                $this->logProgress(
                    "✓ Batch processed: {$batchCount} records | " .
                    "Inserted: {$actualInserted} | " .
                    "Duplicates skipped: {$duplicatesInBatch}"
                );
            }
            
        } catch (\Exception $e) {
            $this->logProgress("Batch operation failed, trying individual operations: " . $e->getMessage(), 'warning');
            
            $successInBatch = 0;
            $failedInBatch = 0;
            
            foreach ($this->batchData as $index => $data) {
                try {
                    if ($this->replaceData) {
                        $customerId = $data['customer_id'];
                        $existing = DB::table('customers')
                            ->where('customer_id', $customerId)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if ($existing) {
                            DB::table('customers')
                                ->where('customer_id', $customerId)
                                ->update($data);
                            $this->updatedCount++;
                        } else {
                            $data['created_at'] = now();
                            DB::table('customers')->insert($data);
                            $this->insertedCount++;
                        }
                        $successInBatch++;
                    } else {
                        $result = DB::table('customers')->insertOrIgnore($data);
                        if ($result) {
                            $this->insertedCount++;
                            $successInBatch++;
                        } else {
                            $this->duplicateCount++;
                        }
                    }
                    
                } catch (\Exception $individualError) {
                    $this->errorCount++;
                    $failedInBatch++;
                    
                    if ($this->errorCount <= 10) {
                        $this->logProgress(
                            "Individual operation failed for record {$index}: " . 
                            $individualError->getMessage(),
                            'warning'
                        );
                    }
                }
            }
            
            $this->logProgress(
                "Individual operations: {$successInBatch} successful, {$failedInBatch} failed"
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
        $finalCount = Customer::count();
        
        $this->logProgress("=== IMPORT COMPLETED ===");
        $this->logProgress("Total rows processed: {$this->rowCount}");
        $this->logProgress("Records inserted: {$this->insertedCount}");
        $this->logProgress("Records updated: {$this->updatedCount}");
        $this->logProgress("Duplicates skipped: {$this->duplicateCount}");
        $this->logProgress("Rows skipped (missing data): {$this->skipCount}");
        $this->logProgress("Errors: {$this->errorCount}");
        $this->logProgress("Invalid dates: {$this->invalidDatesCount}");
        $this->logProgress("Duration: {$duration}s");
        $this->logProgress("Average rate: " . round($this->rowCount / $duration, 2) . " rows/sec");
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
        if (empty($value) || strtoupper($value) === 'NULL' || $value === 0 || $value === '0' || $value === '00:00.0') {
            return null;
        }

        $valueStr = (string)$value;
        
        // Skip invalid dates
        if (preg_match('/^(\d{1,2})\/0{1,2}\/0{1,4}$/i', $valueStr) || 
            preg_match('/^0{1,2}\/0{1,2}\/\d{1,4}$/i', $valueStr) ||
            preg_match('/^0{1,2}:0{1,2}\.\d$/i', $valueStr)) {
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
            
            if ($parsedDate && ($parsedDate->year < 1900 || $parsedDate->year > 2150)) {
                $this->invalidDatesCount++;
                return null;
            }
            
            return $parsedDate ? $parsedDate->format('Y-m-d') : null;
            
        } catch (\Exception $e) {
            $this->invalidDatesCount++;
            return null;
        }
    }

    private function cleanValue($value)
    {
        if (is_null($value) || $value === '' || strtoupper($value) === 'NULL' || $value === 'NULL') {
            return null;
        }

        return trim($value);
    }

    private function cleanEmail($value)
    {
        $cleaned = $this->cleanValue($value);
        
        if ($cleaned && filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return strtolower($cleaned);
        }
        
        return null;
    }

    private function cleanPhone($value)
    {
        $cleaned = $this->cleanValue($value);
        
        if ($cleaned) {
            // Remove all non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $cleaned);
            return $phone ?: null;
        }
        
        return null;
    }

    public function getStats()
    {
        $duration = round(microtime(true) - $this->startTime, 2);
        
        return [
            'total_rows' => $this->rowCount,
            'inserted' => $this->insertedCount,
            'updated' => $this->updatedCount,
            'duplicates_skipped' => $this->duplicateCount,
            'rows_skipped' => $this->skipCount,
            'errors' => $this->errorCount,
            'invalid_dates' => $this->invalidDatesCount,
            'duration' => $duration,
            'rate' => $duration > 0 ? round($this->rowCount / $duration, 2) : 0,
        ];
    }
}