<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $account->old_acct_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            padding: 20px;
        }

        /* Header */
        .company-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .company-address {
            font-size: 9px;
            margin-top: 3px;
        }

        /* Main Content Area */
        .content-wrapper {
            display: table;
            width: 100%;
            margin-top: 15px;
        }

        /* Left side - Customer Info */
        .customer-section {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            padding-right: 15px;
        }

        .customer-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .customer-address {
            font-size: 9px;
            line-height: 1.5;
        }

        /* Right side - Logo and Statement Box */
        .statement-section {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .logo-container {
            text-align: right;
            margin-bottom: 10px;
        }

        .logo {
            display: inline-block;
            background: linear-gradient(135deg, #4CAF50 0%, #81C784 100%);
            padding: 15px 25px;
            color: white;
            text-align: center;
        }

        .logo-text {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .logo-subtext {
            font-size: 8px;
            letter-spacing: 2px;
            margin-top: 2px;
        }

        /* Statement Box */
        .statement-box {
            border: 2px solid #000;
            margin-top: 10px;
        }

        .statement-title {
            background-color: #000;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            letter-spacing: 2px;
        }

        .statement-body {
            padding: 0;
        }

        .statement-body table {
            width: 100%;
            border-collapse: collapse;
        }

        .statement-body tr {
            border-bottom: 1px solid #ddd;
        }

        .statement-body tr:last-child {
            border-bottom: none;
        }

        .statement-body td {
            padding: 5px 10px;
            font-size: 9px;
        }

        .statement-body td:first-child {
            font-weight: bold;
            width: 55%;
        }

        .statement-body td:last-child {
            text-align: right;
            font-weight: normal;
        }

        /* Transactions Table */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #000;
        }

        .transactions-table thead {
            background-color: #f5f5f5;
        }

        .transactions-table th {
            padding: 8px 5px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #000;
            text-align: center;
        }

        .transactions-table td {
            padding: 6px 5px;
            font-size: 9px;
            border: 1px solid #000;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-left {
            text-align: left !important;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Company Header -->
    <div class="company-header">
        <div class="company-name">LUKEFIELD FINANCE LIMITED</div>
        <div class="company-address">4, Karimu Kotun Street, Victoria Island, Lagos.</div>
    </div>

    <!-- Main Content: Customer Info (Left) + Logo & Statement Box (Right) -->
    <div class="content-wrapper">
        <!-- Left: Customer Information -->
        <div class="customer-section">
            <div class="customer-name">{{ strtoupper($account->customerid ?? 'CUSTOMER NAME') }}</div>
            <div class="customer-address">
                {{ strtoupper($account->branch ?? 'BRANCH') }}<br>
                LAGOS, NIGERIA
            </div>
        </div>

        <!-- Right: Logo and Statement Box -->
        <div class="statement-section">
            <!-- Logo -->
            <div class="logo-container">
                <div class="logo">
                    <div class="logo-text">LUKEFIELD</div>
                    <div class="logo-subtext">FINANCE COMPANY LIMITED</div>
                </div>
            </div>

            <!-- Statement Box -->
            <div class="statement-box">
                <div class="statement-title">STATEMENT</div>
                <div class="statement-body">
                    <table>
                        <tr>
                            <td>STATEMENT PERIOD</td>
                            <td>{{ $startDate->format('d-M-Y') }} To {{ $endDate->format('d-M-Y') }}</td>
                        </tr>
                        <tr>
                            <td>ACCOUNT NUMBER</td>
                            <td>{{ $account->old_acct_no }}</td>
                        </tr>
                        <tr>
                            <td>NUBAN</td>
                            <td>{{ $account->account_id ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>PRODUCT NAME</td>
                            <td>{{ $account->product_code ?? 'CUSTOMER MASTER ACCOUNT' }}</td>
                        </tr>
                        <tr>
                            <td>OPENING BALANCE</td>
                            <td>{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                        <tr>
                            <td>TOTAL DEBIT AMOUNT</td>
                            <td>{{ number_format($totalDebit, 2) }}</td>
                        </tr>
                        <tr>
                            <td>TOTAL CREDIT AMOUNT</td>
                            <td>{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                        <tr>
                            <td>CLOSING BALANCE</td>
                            <td>{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                        <tr>
                            <td>RECOVERY FEE</td>
                            <td>0.00</td>
                        </tr>
                        <tr>
                            <td>ACCRUED INTEREST</td>
                            <td>0.00</td>
                        </tr>
                        <tr>
                            <td>CURRENCY</td>
                            <td>NGN</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <table class="transactions-table">
        <thead>
            <tr>
                <th style="width: 4%;">S/N</th>
                <th style="width: 12%;">Transaction<br>Date</th>
                <th style="width: 12%;">Value Date</th>
                <th style="width: 40%;">Narration</th>
                <th style="width: 11%;">Debit</th>
                <th style="width: 11%;">Credit</th>
                <th style="width: 10%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $transaction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $transaction->transaction_date ? $transaction->transaction_date->format('d-M-Y') : 'N/A' }}</td>
                    <td class="text-center">{{ $transaction->financial_date ? $transaction->financial_date->format('d-M-Y') : ($transaction->transaction_date ? $transaction->transaction_date->format('d-M-Y') : 'N/A') }}</td>
                    <td>{{ $transaction->narration ?? '-' }}</td>
                    <td class="text-right">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '' }}</td>
                    <td class="text-right">{{ number_format($transaction->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>