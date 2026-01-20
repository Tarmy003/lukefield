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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class StatementOfAccountExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithDrawings
{
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
        $this->logoPath = $logoPath ?? public_path('images/logo.png'); // Default logo path
        
        // Get account details
        $this->account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)->first();
        
        // Get transactions
        $this->transactions = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->dateRange($startDate, $endDate)
            ->active()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('transaction_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        
        // Calculate totals
        $this->totalDebit = $this->transactions->sum('debit');
        $this->totalCredit = $this->transactions->sum('credit');
        $this->openingBalance = $this->transactions->first()->opening_balance ?? 0;
        $this->closingBalance = $this->transactions->last()->balance_after ?? $this->account->bk_balance_corrected ?? 0;
    }

    public function collection()
    {
        $data = collect();
        
        // Row 1-2: Logo space (A-B) and Company Name (C-G)
        $data->push(['', '', 'LUKEFIELD FINANCE COMPANY LIMITED']);
        $data->push(['', '', '4, Karimu Kotun Street, Victoria Island, Lagos.']);
        $data->push(['']);
        
        // Customer Details (Left side) and Statement Info (Right side will be merged)
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
        
        // Row 18: Transaction Headers
        $data->push(['S/N', 'Transaction Date', 'Value Date', 'Narration', 'Debit', 'Credit', 'Balance']);
        
        // Transaction data
        foreach ($this->transactions as $index => $transaction) {
            $data->push([
                $index + 1, // S/N
                $transaction->transaction_date ? $transaction->transaction_date->format('d-M-Y') : '',
                $transaction->financial_date ? $transaction->financial_date->format('d-M-Y') : '',
                $transaction->narration ?? '',
                $transaction->debit > 0 ? number_format($transaction->debit, 2) : '',
                $transaction->credit > 0 ? number_format($transaction->credit, 2) : '',
                number_format($transaction->balance_after, 2),
            ]);
        }
        
        return $data;
    }

    public function drawings()
    {
        // Only add logo if file exists
        if (!file_exists($this->logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath($this->logoPath);
        $drawing->setHeight(60); // Adjust height as needed
        $drawing->setCoordinates('A1'); // Position at A1
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(5);

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 18; // Row where transaction headers start
        
        return [
            // Company name styling
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            
            // Statement header (dark background)
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']],
            ],
            
            // Transaction headers
            $headerRow => [
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
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 18;
                $lastRow = $sheet->getHighestRow();
                
                // Merge company name across columns C-G (leaving A-B for logo)
                $sheet->mergeCells('C1:G1');
                $sheet->mergeCells('C2:G2');
                
                // Statement info header - merge "STATEMENT" cell
                $sheet->mergeCells('F4:G4');
                
                // Style statement info section (right side)
                $sheet->getStyle('E4:G16')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                
                // Bold the labels in statement info
                $sheet->getStyle('E6:E16')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                
                // Right align values in statement info
                $sheet->getStyle('F6:G16')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
                
                // Style transaction data rows
                $sheet->getStyle('A' . ($headerRow + 1) . ':G' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                ]);
                
                // Right align numeric columns (S/N, Debit, Credit, Balance)
                $sheet->getStyle('A' . ($headerRow + 1) . ':A' . $lastRow)->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                
                $sheet->getStyle('E' . ($headerRow + 1) . ':G' . $lastRow)->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
                
                // Narration column - wrap text
                $sheet->getStyle('D' . ($headerRow + 1) . ':D' . $lastRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'wrapText' => true,
                    ],
                ]);
                
                // Set row height for better readability
                for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }
            },
        ];
    }
}