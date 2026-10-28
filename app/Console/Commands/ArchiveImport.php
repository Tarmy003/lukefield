<?php

namespace App\Console\Commands;

use App\Models\NominalAccountsTrxPostingHistory;
use App\Models\SavingsAndCurrentAccount;
use Carbon\Carbon;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Loads a BankOne extract into the archive from the server shell.
 *
 * Why a command and not the web upload:
 *  - No upload size limit and no web-server timeout, so a 200,000-row file
 *    cannot die halfway through the way the browser imports did.
 *  - The whole load runs in ONE database transaction. Old rows are removed and
 *    new rows inserted together; if anything fails, or the totals do not match
 *    the BankOne control totals, everything is undone and the archive is left
 *    exactly as it was.
 *  - The file is read twice: first to check every row without writing, then to
 *    load. Bad rows are reported by line number before anything is touched.
 *  - Amounts are handled as exact strings and totalled in kobo (integers), never
 *    as floats, so totals agree to the kobo with the SSMS control totals.
 *
 * Usage:
 *   php artisan archive:import transactions storage/app/imports/transactions.txt --dry-run
 *   php artisan archive:import transactions storage/app/imports/transactions.txt --replace \
 *       --expect-rows=202348 --expect-debit=1338942684182.24 --expect-credit=1339580857097.65
 */
class ArchiveImport extends Command
{
    protected $signature = 'archive:import
        {type : What the file holds: accounts or transactions}
        {file : Path to the SSMS export (.txt tab-delimited, or .csv)}
        {--replace : Remove the rows already in the table and load the file, in one database transaction}
        {--append : Add to a table that already holds rows (only for a deliberate top-up)}
        {--expect-rows= : Row count from the BankOne control totals; the load is undone if it differs}
        {--expect-debit= : Total debit from the BankOne control totals (transactions only)}
        {--expect-credit= : Total credit from the BankOne control totals (transactions only)}
        {--delimiter= : tab or comma; defaults to tab for .txt files and comma for anything else}
        {--dry-run : Read and check the whole file without writing anything}';

    protected $description = 'Load a BankOne extract into the archive as one all-or-nothing database transaction, checked against control totals';

    private const BATCH_SIZE = 1000;

    /** Column order of BankOne_Transaction_Extract_CutOff_30Jun2026.sql, used only when the file has no header row. */
    private const TRANSACTION_LAYOUT = [
        'transactionorder', 'customerid', 'oldacctno', 'accountid', 'accounttype', 'transactionid',
        'transactiondate', 'financialdate', 'referenceid', 'narration', 'debit', 'credit',
        'openingbalance', 'balanceafter', 'entrycode', 'recordtype', 'postingtype', 'instrumentno',
        'isreversed', 'reversalreferenceno', 'productcode', 'branch', 'accountstatus', 'dateopened',
    ];

    /** Column order of the February transaction extract (no AccountType column). */
    private const TRANSACTION_LAYOUT_FEBRUARY = [
        'transactionorder', 'customerid', 'oldacctno', 'accountid', 'transactionid',
        'transactiondate', 'financialdate', 'referenceid', 'narration', 'debit', 'credit',
        'openingbalance', 'balanceafter', 'entrycode', 'recordtype', 'postingtype', 'instrumentno',
        'isreversed', 'reversalreferenceno', 'productcode', 'branch', 'accountstatus', 'dateopened',
    ];

    /** Column order of the account master file the web import expects. */
    private const ACCOUNT_LAYOUT = [
        'customerid', 'oldacctno', 'branch', 'productcode', 'bkbalancecorrected', 'accruedtilldate',
        'lastaccrueddate', 'lienstatus', 'accountstatus', 'accounttier', 'dateopened',
    ];

    private const REQUIRED_COLUMNS = [
        'transactions' => ['customerid', 'oldacctno', 'transactionid', 'transactiondate', 'debit', 'credit'],
        'accounts'     => ['customerid', 'oldacctno'],
    ];

    private string $type;

    /** @var array<string,int> columns that exist in the target table (flipped for fast lookup) */
    private array $tableColumns = [];

    /** @var array<string,int> optional dates that were blank-ed because they were not real dates */
    private array $dateWarnings = [];

    private bool $positionalLayout = false;

    public function handle(): int
    {
        $this->type = strtolower((string) $this->argument('type'));
        if (! in_array($this->type, ['accounts', 'transactions'], true)) {
            $this->error('The first argument must be "accounts" or "transactions".');
            return self::FAILURE;
        }

        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read the file: {$path}");
            return self::FAILURE;
        }

        if ($this->option('replace') && $this->option('append')) {
            $this->error('Use either --replace or --append, not both.');
            return self::FAILURE;
        }

        if ($this->type === 'accounts' && ($this->option('expect-debit') !== null || $this->option('expect-credit') !== null)) {
            $this->error('--expect-debit and --expect-credit apply to transactions only.');
            return self::FAILURE;
        }

        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $model = $this->type === 'transactions' ? new NominalAccountsTrxPostingHistory() : new SavingsAndCurrentAccount();
        $table = $model->getTable();
        $db = DB::connection($model->getConnectionName());
        $db->disableQueryLog();
        $this->tableColumns = array_flip(Schema::connection($model->getConnectionName())->getColumnListing($table));

        try {
            $delimiter = $this->resolveDelimiter($path);
            $expected = $this->expectedTotals();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line('');
        $this->info("Archive import: {$this->type}");
        $this->line("  File:      {$path} (" . round(filesize($path) / 1048576, 2) . ' MB)');
        $this->line("  Table:     {$table}");
        $this->line('  Delimiter: ' . ($delimiter === "\t" ? 'tab' : 'comma'));
        $this->line('');

        // ------------------------------------------------------------------
        // PASS 1: read and check every row. Nothing is written.
        // ------------------------------------------------------------------
        $this->info('Step 1 of 2: checking the file (nothing is written yet)...');

        try {
            $scan = $this->scanFile($path, $delimiter);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($this->positionalLayout) {
            $this->warn('  The file has no header row, so columns were matched by position. Check the sample row below.');
        }

        $this->reportScan($scan);

        if ($scan['errors'] !== []) {
            $this->line('');
            $this->error(sprintf('%s problem row(s) found. Nothing was written. First %d:', number_format($scan['error_count']), count($scan['errors'])));
            foreach ($scan['errors'] as $message) {
                $this->line("  {$message}");
            }
            return self::FAILURE;
        }

        $mismatches = $this->compareWithExpected($scan, $expected);
        if ($mismatches !== []) {
            $this->line('');
            $this->error('The file does not match the BankOne control totals. Nothing was written.');
            foreach ($mismatches as $message) {
                $this->line("  {$message}");
            }
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('');
            $this->info('Dry run finished: the file is clean. Run again without --dry-run to load it.');
            return self::SUCCESS;
        }

        $existingRows = (int) $db->table($table)->count();
        if ($existingRows > 0 && ! $this->option('replace') && ! $this->option('append')) {
            $this->line('');
            $this->error(sprintf(
                'The table already holds %s rows. Use --replace to swap them for this file (recommended), or --append to add to them.',
                number_format($existingRows)
            ));
            return self::FAILURE;
        }

        // ------------------------------------------------------------------
        // PASS 2: load inside one transaction, verify, then commit.
        // ------------------------------------------------------------------
        $this->line('');
        $this->info('Step 2 of 2: loading in one database transaction...');

        try {
            $result = $db->transaction(function () use ($db, $table, $path, $delimiter, $scan, $expected, $existingRows) {
                if ($this->option('replace')) {
                    $db->table($table)->delete();
                    $this->line('  Removed the ' . number_format($existingRows) . ' existing rows (undone automatically if the load fails).');
                }

                $before = $this->tableTotals($db, $table);
                $this->loadFile($db, $table, $path, $delimiter, $scan['rows']);
                $after = $this->tableTotals($db, $table);

                $loaded = [
                    'rows'   => $after['rows'] - $before['rows'],
                    'debit'  => $after['debit'] - $before['debit'],
                    'credit' => $after['credit'] - $before['credit'],
                    'balance' => $after['balance'] - $before['balance'],
                ];

                $problems = [];
                if ($loaded['rows'] !== $scan['rows']) {
                    $problems[] = sprintf('Rows in the file: %s, rows saved: %s', number_format($scan['rows']), number_format($loaded['rows']));
                }
                if ($this->type === 'transactions') {
                    if ($loaded['debit'] !== $scan['debit']) {
                        $problems[] = 'Debit in the file: ' . $this->naira($scan['debit']) . ', debit saved: ' . $this->naira($loaded['debit']);
                    }
                    if ($loaded['credit'] !== $scan['credit']) {
                        $problems[] = 'Credit in the file: ' . $this->naira($scan['credit']) . ', credit saved: ' . $this->naira($loaded['credit']);
                    }
                }
                $problems = array_merge($problems, $this->compareWithExpected($loaded, $expected));

                if ($problems !== []) {
                    throw new RuntimeException("The saved data did not match, so the load was undone:\n  " . implode("\n  ", $problems));
                }

                return ['loaded' => $loaded, 'table' => $after];
            });
        } catch (Throwable $e) {
            $this->line('');
            $this->error('Load failed. Nothing was changed: the archive is exactly as it was before.');
            $this->line('  ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line('');
        $this->info('Load complete and verified.');
        $rows = [
            ['Rows loaded', number_format($result['loaded']['rows'])],
            ['Rows now in table', number_format($result['table']['rows'])],
        ];
        if ($this->type === 'transactions') {
            $rows[] = ['Total debit loaded', $this->naira($result['loaded']['debit'])];
            $rows[] = ['Total credit loaded', $this->naira($result['loaded']['credit'])];
        } elseif (isset($this->tableColumns['bk_balance_corrected'])) {
            $rows[] = ['Total book balance loaded', $this->naira($result['loaded']['balance'])];
        }
        $this->table(['Check', 'Result'], $rows);

        return self::SUCCESS;
    }

    // ======================================================================
    // File reading
    // ======================================================================

    /**
     * Pass 1: read every row, convert it, total it, and collect problems.
     */
    private function scanFile(string $path, string $delimiter): array
    {
        $scan = [
            'rows' => 0, 'debit' => 0, 'credit' => 0, 'balance' => 0,
            'errors' => [], 'error_count' => 0,
            'first_date' => null, 'last_date' => null,
            'accounts' => [], 'no_leading_zero' => 0,
            'sample' => null, 'unstored' => [],
        ];

        $keyField = $this->type === 'transactions' ? 'transaction_id' : 'old_acct_no';
        $seenKeys = [];

        foreach ($this->readRecords($path, $delimiter) as [$line, $record, $error]) {
            if ($error === null) {
                $key = (string) $record[$keyField];
                if (isset($seenKeys[$key])) {
                    $error = sprintf('%s %s already appears on line %d', $keyField === 'transaction_id' ? 'TransactionID' : 'OldAcctNo', $key, $seenKeys[$key]);
                } else {
                    $seenKeys[$key] = $line;
                }
            }

            if ($error !== null) {
                $scan['error_count']++;
                if (count($scan['errors']) < 20) {
                    $scan['errors'][] = "Line {$line}: {$error}";
                }
                continue;
            }

            $scan['rows']++;
            $scan['sample'] ??= $record;
            $scan['accounts'][$record['old_acct_no']] = true;

            if (ctype_digit($record['old_acct_no']) && $record['old_acct_no'][0] !== '0') {
                $scan['no_leading_zero']++;
            }

            if ($this->type === 'transactions') {
                $scan['debit'] += $this->kobo($record['debit']);
                $scan['credit'] += $this->kobo($record['credit']);
                $date = $record['transaction_date'];
                if ($scan['first_date'] === null || $date < $scan['first_date']) {
                    $scan['first_date'] = $date;
                }
                if ($scan['last_date'] === null || $date > $scan['last_date']) {
                    $scan['last_date'] = $date;
                }
            } elseif ($record['bk_balance_corrected'] !== null) {
                $scan['balance'] += $this->kobo($record['bk_balance_corrected']);
            }
        }

        if ($scan['sample'] !== null) {
            $scan['unstored'] = array_keys(array_diff_key($scan['sample'], $this->tableColumns));
        }

        $scan['accounts'] = count($scan['accounts']);

        return $scan;
    }

    /**
     * Pass 2: insert the rows in batches. Runs inside the caller's transaction.
     */
    private function loadFile(Connection $db, string $table, string $path, string $delimiter, int $expectedRows): void
    {
        $bar = $this->output->createProgressBar($expectedRows);
        $bar->start();

        $batch = [];
        $lastLine = 0;

        foreach ($this->readRecords($path, $delimiter) as [$line, $record, $error]) {
            if ($error !== null) {
                throw new RuntimeException("Line {$line}: {$error}");
            }

            $batch[] = array_intersect_key($record, $this->tableColumns);
            $lastLine = $line;

            if (count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($db, $table, $batch, $lastLine);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->insertBatch($db, $table, $batch, $lastLine);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->line('');
    }

    private function insertBatch(Connection $db, string $table, array $batch, int $lastLine): void
    {
        try {
            $db->table($table)->insert($batch);
        } catch (QueryException $e) {
            $reason = $e->errorInfo[2] ?? $e->getMessage();
            throw new RuntimeException("The database rejected the batch ending at line {$lastLine}: {$reason}");
        }
    }

    /**
     * Yields [lineNumber, record|null, error|null] for every data row.
     */
    private function readRecords(string $path, string $delimiter): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open the file: {$path}");
        }

        // SSMS does not quote fields in tab-delimited output, so a quote mark
        // inside a narration must not be treated as an enclosure.
        $enclosure = $delimiter === "\t" ? "\x01" : '"';
        $layout = null;
        $line = 0;
        $now = now()->format('Y-m-d H:i:s');
        $this->positionalLayout = false;

        try {
            while (($fields = fgetcsv($handle, 0, $delimiter, $enclosure, '')) !== false) {
                $line++;

                if ($fields === [null] || (count($fields) === 1 && trim((string) $fields[0]) === '')) {
                    continue;
                }

                if ($line === 1) {
                    $fields[0] = $this->stripBom((string) $fields[0]);
                }

                if ($layout === null) {
                    $headers = array_map(fn ($h) => $this->normaliseHeader((string) $h), $fields);
                    if (in_array('oldacctno', $headers, true)) {
                        $this->assertRequiredColumns($headers);
                        $layout = $headers;
                        continue;
                    }
                    $layout = $this->positionalLayoutFor(count($fields));
                    $this->positionalLayout = true;
                }

                if (count($fields) !== count($layout)) {
                    yield [$line, null, sprintf(
                        'expected %d columns but found %d. A field probably contains the delimiter or a line break.',
                        count($layout),
                        count($fields)
                    )];
                    continue;
                }

                $raw = array_combine($layout, array_map(fn ($v) => $this->clean($v), $fields));

                try {
                    $record = $this->type === 'transactions'
                        ? $this->transactionRecord($raw, $now)
                        : $this->accountRecord($raw, $now);
                    yield [$line, $record, null];
                } catch (RuntimeException $e) {
                    yield [$line, null, $e->getMessage()];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    // ======================================================================
    // Row conversion
    // ======================================================================

    private function transactionRecord(array $r, string $now): array
    {
        $accountNo = $r['oldacctno'] ?? null;
        $customerId = $r['customerid'] ?? null;
        $transactionId = $this->integer($r['transactionid'] ?? null, 'TransactionID');
        $transactionDate = $this->date($r['transactiondate'] ?? null, 'TransactionDate');

        if ($accountNo === null) {
            throw new RuntimeException('OldAcctNo is empty');
        }
        if ($customerId === null) {
            throw new RuntimeException('customerid is empty');
        }
        if ($transactionId === null) {
            throw new RuntimeException('TransactionID is empty');
        }
        if ($transactionDate === null) {
            throw new RuntimeException("TransactionDate '" . ($r['transactiondate'] ?? '') . "' is missing or not a real date");
        }

        return [
            'transaction_order'     => $this->integer($r['transactionorder'] ?? null, 'TransactionOrder'),
            'customerid'            => $customerId,
            'old_acct_no'           => $accountNo,
            'account_id'            => $this->integer($r['accountid'] ?? null, 'AccountID'),
            'account_type'          => $r['accounttype'] ?? null,
            'transaction_id'        => $transactionId,
            'transaction_date'      => $transactionDate,
            'financial_date'        => $this->date($r['financialdate'] ?? null, 'FinancialDate'),
            'reference_id'          => $r['referenceid'] ?? null,
            'narration'             => $r['narration'] ?? null,
            'debit'                 => $this->decimal($r['debit'] ?? null, 'Debit') ?? '0',
            'credit'                => $this->decimal($r['credit'] ?? null, 'Credit') ?? '0',
            'opening_balance'       => $this->decimal($r['openingbalance'] ?? null, 'OpeningBalance') ?? '0',
            'balance_after'         => $this->decimal($r['balanceafter'] ?? null, 'BalanceAfter') ?? '0',
            'entry_code'            => $r['entrycode'] ?? null,
            'record_type'           => $this->integer($r['recordtype'] ?? null, 'RecordType'),
            'posting_type'          => $this->integer($r['postingtype'] ?? null, 'PostingType'),
            'instrument_no'         => $r['instrumentno'] ?? null,
            'is_reversed'           => $this->boolean($r['isreversed'] ?? null, 'IsReversed'),
            'reversal_reference_no' => $r['reversalreferenceno'] ?? null,
            'product_code'          => $r['productcode'] ?? null,
            'branch'                => $r['branch'] ?? null,
            'account_status'        => $this->integer($r['accountstatus'] ?? null, 'AccountStatus'),
            'date_opened'           => $this->date($r['dateopened'] ?? null, 'DateOpened'),
            'created_at'            => $now,
            'updated_at'            => $now,
        ];
    }

    private function accountRecord(array $r, string $now): array
    {
        $accountNo = $r['oldacctno'] ?? null;
        $customerId = $r['customerid'] ?? null;

        if ($accountNo === null) {
            throw new RuntimeException('OldAcctNo is empty');
        }
        if ($customerId === null) {
            throw new RuntimeException('customerid is empty');
        }

        return [
            'customerid'           => $customerId,
            'old_acct_no'          => $accountNo,
            'account_type'         => $r['accounttype'] ?? null,
            'branch'               => $r['branch'] ?? null,
            'product_code'         => $r['productcode'] ?? null,
            'bk_balance_corrected' => $this->decimal($r['bkbalancecorrected'] ?? null, 'BkBalance(Corrected)') ?? '0',
            'accrued_till_date'    => $this->decimal($r['accruedtilldate'] ?? null, 'AccruedTillDate'),
            'last_accrued_date'    => $this->date($r['lastaccrueddate'] ?? null, 'LastAccruedDate'),
            'lien_status'          => $this->flag($r['lienstatus'] ?? null),
            // Same defaults as the web import, so both routes store the same values.
            'account_status'       => $r['accountstatus'] ?? '1',
            'account_tier'         => $r['accounttier'] ?? '1',
            'date_opened'          => $this->date($r['dateopened'] ?? null, 'DateOpened'),
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
    }

    // ======================================================================
    // Value parsing
    // ======================================================================

    /** Trims, fixes non-UTF-8 text, and turns '' and the word NULL into null. */
    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! mb_check_encoding($value, 'UTF-8')) {
            // SSMS often saves in the Windows code page; MySQL rejects those bytes as UTF-8.
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }
        $value = trim($value);

        return ($value === '' || strcasecmp($value, 'NULL') === 0) ? null : $value;
    }

    private function integer(?string $value, string $label): ?int
    {
        if ($value === null) {
            return null;
        }
        if (preg_match('/^-?\d+(\.0+)?$/', $value)) {
            return (int) $value;
        }
        throw new RuntimeException("{$label} '{$value}' is not a whole number");
    }

    /** Returns an exact decimal string; amounts are never converted to float. */
    private function decimal(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalised = str_replace([',', ' '], '', $value);
        if (! preg_match('/^-?\d+(\.\d+)?$/', $normalised)) {
            throw new RuntimeException("{$label} '{$value}' is not a number");
        }
        return $normalised;
    }

    private function boolean(?string $value, string $label): int
    {
        if ($value === null) {
            return 0;
        }
        $v = strtolower($value);
        if (in_array($v, ['1', 'true', 'yes', 'y'], true)) {
            return 1;
        }
        if (in_array($v, ['0', 'false', 'no', 'n'], true)) {
            return 0;
        }
        throw new RuntimeException("{$label} '{$value}' is not true/false");
    }

    /** True/False words become 1/0; anything else is stored as given. */
    private function flag(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return match (strtolower($value)) {
            'true', 'yes', 'y' => '1',
            'false', 'no', 'n' => '0',
            default => $value,
        };
    }

    /**
     * Returns 'Y-m-d H:i:s' or null. Placeholders such as '01/00/00', Excel
     * serial 0, and dates outside 1950-2150 become null and are counted, so the
     * load is not blocked by BankOne's dummy dates in optional fields.
     */
    private function date(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = null;

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?$/', $value)) {
                $date = Carbon::createFromFormat('Y-m-d H:i:s', substr($value, 0, 19));
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $value . ' 00:00:00');
            } elseif (preg_match('#^(\d{1,2}/0{1,2}/\d{1,4}|0{1,2}/\d{1,2}/\d{1,4})$#', $value)) {
                $date = null; // day or month of zero: a placeholder, not a date
            } elseif (preg_match('/^\d+(\.\d+)?$/', $value)) {
                $serial = (int) floor((float) $value); // Excel serial date
                $date = $serial >= 1 ? Carbon::create(1899, 12, 30, 0, 0, 0)->addDays($serial) : null;
            } else {
                $date = Carbon::parse($value);
            }
        } catch (Throwable) {
            $date = null;
        }

        if (! $date instanceof Carbon || $date->year < 1950 || $date->year > 2150) {
            $this->dateWarnings[$label] = ($this->dateWarnings[$label] ?? 0) + 1;
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }

    /** Exact conversion of a decimal string to kobo, rounding half up at the third decimal. */
    private function kobo(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad($fraction, 3, '0');

        $kobo = ((int) $whole) * 100 + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $kobo++;
        }

        return $negative ? -$kobo : $kobo;
    }

    private function naira(int $kobo): string
    {
        $sign = $kobo < 0 ? '-' : '';
        $kobo = abs($kobo);

        return $sign . 'N' . number_format(intdiv($kobo, 100)) . '.' . str_pad((string) ($kobo % 100), 2, '0', STR_PAD_LEFT);
    }

    // ======================================================================
    // Checks and reporting
    // ======================================================================

    private function expectedTotals(): array
    {
        $expected = ['rows' => null, 'debit' => null, 'credit' => null];

        $rows = $this->option('expect-rows');
        if ($rows !== null) {
            $rows = str_replace(',', '', (string) $rows);
            if (! ctype_digit($rows)) {
                throw new RuntimeException("--expect-rows must be a whole number, got '{$this->option('expect-rows')}'");
            }
            $expected['rows'] = (int) $rows;
        }

        foreach (['debit', 'credit'] as $field) {
            $value = $this->option("expect-{$field}");
            if ($value !== null) {
                $decimal = $this->decimal((string) $value, "--expect-{$field}");
                $expected[$field] = $this->kobo($decimal);
            }
        }

        return $expected;
    }

    private function compareWithExpected(array $actual, array $expected): array
    {
        $problems = [];

        if ($expected['rows'] !== null && $actual['rows'] !== $expected['rows']) {
            $problems[] = sprintf('Rows: expected %s, got %s', number_format($expected['rows']), number_format($actual['rows']));
        }
        if ($expected['debit'] !== null && $actual['debit'] !== $expected['debit']) {
            $problems[] = 'Debit: expected ' . $this->naira($expected['debit']) . ', got ' . $this->naira($actual['debit']);
        }
        if ($expected['credit'] !== null && $actual['credit'] !== $expected['credit']) {
            $problems[] = 'Credit: expected ' . $this->naira($expected['credit']) . ', got ' . $this->naira($actual['credit']);
        }

        return $problems;
    }

    private function tableTotals(Connection $db, string $table): array
    {
        $totals = ['rows' => (int) $db->table($table)->count(), 'debit' => 0, 'credit' => 0, 'balance' => 0];

        if ($this->type === 'transactions') {
            $sums = $db->table($table)
                ->selectRaw('COALESCE(SUM(debit), 0) AS debit_total, COALESCE(SUM(credit), 0) AS credit_total')
                ->first();
            $totals['debit'] = $this->kobo((string) $sums->debit_total);
            $totals['credit'] = $this->kobo((string) $sums->credit_total);
        } elseif (isset($this->tableColumns['bk_balance_corrected'])) {
            $sum = $db->table($table)->selectRaw('COALESCE(SUM(bk_balance_corrected), 0) AS balance_total')->first();
            $totals['balance'] = $this->kobo((string) $sum->balance_total);
        }

        return $totals;
    }

    private function reportScan(array $scan): void
    {
        $rows = [
            ['Data rows', number_format($scan['rows'])],
            ['Accounts', number_format($scan['accounts'])],
        ];

        if ($this->type === 'transactions') {
            $rows[] = ['Total debit', $this->naira($scan['debit'])];
            $rows[] = ['Total credit', $this->naira($scan['credit'])];
            $rows[] = ['Earliest transaction', $scan['first_date'] ?? '-'];
            $rows[] = ['Latest transaction', $scan['last_date'] ?? '-'];
        } else {
            $rows[] = ['Total book balance', $this->naira($scan['balance'])];
        }

        $this->table(['File check', 'Result'], $rows);

        foreach ($this->dateWarnings as $label => $count) {
            $this->warn(sprintf('  %s: %s value(s) were not real dates (e.g. 01/00/00 or 0) and will be stored blank.', $label, number_format($count)));
        }

        if ($scan['no_leading_zero'] > 0) {
            $this->warn(sprintf(
                '  %s account number(s) do not start with 0. If the file was opened and saved in Excel, leading zeros may have been lost.',
                number_format($scan['no_leading_zero'])
            ));
        }

        if ($scan['unstored'] !== []) {
            $this->line('  Not stored (no matching column in the table): ' . implode(', ', $scan['unstored']));
        }

        if ($scan['sample'] !== null && ($this->option('dry-run') || $this->positionalLayout)) {
            $this->line('');
            $this->line('  First row as it will be saved:');
            $this->table(['Column', 'Value'], collect($scan['sample'])
                ->only(array_keys($this->tableColumns))
                ->map(fn ($v, $k) => [$k, $v === null ? '(blank)' : (string) $v])
                ->values()
                ->all());
        }
    }

    // ======================================================================
    // Layout helpers
    // ======================================================================

    private function resolveDelimiter(string $path): string
    {
        $option = $this->option('delimiter');

        if ($option === null) {
            return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'txt' ? "\t" : ',';
        }

        return match (strtolower((string) $option)) {
            'tab', '\t' => "\t",
            'comma', ',' => ',',
            default => throw new RuntimeException("--delimiter must be 'tab' or 'comma', got '{$option}'"),
        };
    }

    private function positionalLayoutFor(int $columnCount): array
    {
        $layouts = $this->type === 'transactions'
            ? [count(self::TRANSACTION_LAYOUT) => self::TRANSACTION_LAYOUT, count(self::TRANSACTION_LAYOUT_FEBRUARY) => self::TRANSACTION_LAYOUT_FEBRUARY]
            : [count(self::ACCOUNT_LAYOUT) => self::ACCOUNT_LAYOUT];

        if (! isset($layouts[$columnCount])) {
            throw new RuntimeException(sprintf(
                'The file has no header row and %d columns, which matches no known layout. In SSMS turn on Tools > Options > Query Results > SQL Server > Results to Grid > "Include column headers when copying or saving the results", then save the results again.',
                $columnCount
            ));
        }

        return $layouts[$columnCount];
    }

    private function assertRequiredColumns(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_COLUMNS[$this->type], $headers);
        if ($missing !== []) {
            throw new RuntimeException('The header row is missing: ' . implode(', ', $missing));
        }
    }

    private function normaliseHeader(string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($header));
    }

    private function stripBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
