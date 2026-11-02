<?php

namespace App\Exports;

use App\Models\NominalAccountsTrxPostingHistory;
use App\Models\SavingsAndCurrentAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

/**
 * Excel statement of account.
 *
 * Transaction dates and amounts are written as real Excel dates and numbers,
 * not text, so staff can sort, filter, re-format (e.g. to Short Date) and
 * add them up. Dates display as 15-Feb-2026, the same as before; amounts as
 * 62,779,342.29.
 *
 * Customer ID, account number and NUBAN are written explicitly as text so
 * Excel cannot strip their leading zeros or show them in scientific notation.
 */
class StatementOfAccountExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithDrawings
{
    /** Row holding the transaction column headings; transactions start on the next row. */
    private const HEADER_ROW = 18;

    private const DATE_FORMAT = 'dd-mmm-yyyy';
    private const AMOUNT_FORMAT = '#,##0.00';

    protected $accountNumber;
    protected $startDate;
    protected $endDate;
    protected $account;
    protected $transactions;
    protected $openingBalance;
    protected $closingBalance;
    protected $totalDebit;
    protected $totalCredit;
    protected $logoPath;

    public function __construct($accountNumber, $startDate, $endDate, $logoPath = null)
    {
        $this->accountNumber = $accountNumber;
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        $this->logoPath = $logoPath ?? public_path('images/logo.png');

        $this->account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)->first();

        // Oldest first. transaction_order counts 1 as the MOST RECENT posting on the
        // account, so it is sorted descending; ascending put same-day postings in
        // reverse and could take the opening/closing balance from the wrong row.
        // Same ordering as the on-screen preview and the PDF.
        $this->transactions = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->dateRange($startDate, $endDate)
            ->active()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('transaction_order', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $this->totalDebit = $this->transactions->sum('debit');
        $this->totalCredit = $this->transactions->sum('credit');
        $this->openingBalance = $this->transactions->first()->opening_balance ?? 0;
        $this->closingBalance = $this->transactions->last()->balance_after ?? $this->account->bk_balance_corrected ?? 0;
    }

    public function collection()
    {
        $data = collect();

        // Rows 1-2: logo space (A-B) and company name (C-G)
        $data->push(['', '', 'LUKEFIELD FINANCE COMPANY LIMITED']);
        $data->push(['', '', '4, Karimu Kotun Street, Victoria Island, Lagos.']);
        $data->push(['']);

        // Customer details (left) and statement summary (right)
        $data->push(['Customer Name:', $this->account->customerid ?? 'N/A', '', '', '', 'STATEMENT']);
        $data->push(['', '', '', '', '', '']);
        $data->push(['', '', '', '', 'Statement Period:', $this->startDate->format('d-M-Y') . ' To ' . $this->endDate->format('d-M-Y')]);
        $data->push(['', '', '', '', 'Account Number:', $this->account->old_acct_no ?? 'N/A']);
        $data->push(['', '', '', '', 'NUBAN:', $this->account->nuban ?? 'N/A']);
        $data->push(['', '', '', '', 'Product Name:', $this->account->product_code ?? 'CUSTOMER MASTER ACCOUNT']);
        $data->push(['', '', '', '', 'Opening Balance:', 'NGN ' . number_format($this->openingBalance, 2)]);
        $data->push(['', '', '', '', 'Total Debit Amount:', 'NGN ' . number_format($this->totalDebit, 2)]);
        $data->push(['', '', '', '', 'Total Credit Amount:', 'NGN ' . number_format($this->totalCredit, 2)]);
        $data->push(['', '', '', '', 'Closing Balance:', 'NGN ' . number_format($this->closingBalance, 2)]);
        $data->push(['', '', '', '', 'Recovery Fee:', 'NGN 0.00']);
        $data->push(['', '', '', '', 'Accrued Interest:', 'NGN 0.00']);
        $data->push(['', '', '', '', 'Total Outstanding:', 'NGN 0.00']);
        $data->push(['']);

        // Row 18: transaction headings
        $data->push(['S/N', 'Transaction Date', 'Value Date', 'Narration', 'Debit', 'Credit', 'Balance']);

        // Transactions: dates as Excel date serials, amounts as numbers.
        // Formats are applied in AfterSheet.
        foreach ($this->transactions as $index => $transaction) {
            $data->push([
                $index + 1,
                $this->excelDate($transaction->transaction_date),
                $this->excelDate($transaction->financial_date),
                $transaction->narration ?? '',
                $transaction->debit > 0 ? (float) $transaction->debit : null,
                $transaction->credit > 0 ? (float) $transaction->credit : null,
                (float) $transaction->balance_after,
            ]);
        }

        return $data;
    }

    /**
     * A real Excel date (whole day, no time part) or null.
     * The time is dropped so date filters and Short Date work cleanly; some
     * BankOne postings carry 23:59:59.
     */
    private function excelDate($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

        return (float) ExcelDate::PHPToExcel($date->startOfDay());
    }

    public function drawings()
    {
        if (!file_exists($this->logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath($this->logoPath);
        $drawing->setHeight(60);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(5);

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']],
            ],
            self::HEADER_ROW => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Statement of Account';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // S/N
            'B' => 18,  // Transaction Date
            'C' => 18,  // Value Date
            'D' => 60,  // Narration
            'E' => 18,  // Debit
            'F' => 18,  // Credit
            'G' => 20,  // Balance
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $firstRow = self::HEADER_ROW + 1;
                $lastRow = $sheet->getHighestRow();
                $hasTransactions = $lastRow >= $firstRow;

                // Identifiers as text, so leading zeros survive (000001, 0486...)
                $sheet->setCellValueExplicit('B4', (string) ($this->account->customerid ?? 'N/A'), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F7', (string) ($this->account->old_acct_no ?? 'N/A'), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F8', (string) ($this->account->nuban ?? 'N/A'), DataType::TYPE_STRING);

                $sheet->mergeCells('C1:G1');
                $sheet->mergeCells('C2:G2');
                $sheet->mergeCells('F4:G4');

                $sheet->getStyle('E4:G16')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->getStyle('E6:E16')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle('F6:G16')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                if (!$hasTransactions) {
                    return;
                }

                $sheet->getStyle("A{$firstRow}:G{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                ]);

                $sheet->getStyle("A{$firstRow}:A{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Real dates, shown as 15-Feb-2026; staff can re-format to Short Date
                $sheet->getStyle("B{$firstRow}:C{$lastRow}")->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
                $sheet->getStyle("B{$firstRow}:C{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Real numbers with thousands separators
                $sheet->getStyle("E{$firstRow}:G{$lastRow}")->getNumberFormat()->setFormatCode(self::AMOUNT_FORMAT);
                $sheet->getStyle("E{$firstRow}:G{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                $sheet->getStyle("D{$firstRow}:D{$lastRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'wrapText' => true,
                    ],
                ]);

                for ($row = $firstRow; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }
            },
        ];
    }
}
