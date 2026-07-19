<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .header td {
            vertical-align: top;
            border: none;
        }
        .business-info h1 {
            margin: 0;
            font-size: 24px;
            color: #111;
        }
        .business-info p {
            margin: 2px 0;
            color: #555;
        }
        .report-info h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: #5b21b6; /* violet-800 */
        }
        .report-info p {
            margin: 2px 0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #475569;
        }
        .data-table .text-right { text-align: right; }
        .data-table .text-center { text-align: center; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-emerald { color: #059669; font-weight: bold; }
        .text-rose { color: #e11d48; font-weight: bold; }
        
        .footer {
            width: 100%;
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="header">
        <table cellspacing="0" cellpadding="0">
            <tr>
                <td class="business-info" width="60%">
                    <h1>{{ $businessName ?? 'Business Name' }}</h1>
                    @if($businessAddress ?? false)<p>{{ $businessAddress }}</p>@endif
                    @if($businessPhone ?? false)<p>Phone: {{ $businessPhone }}</p>@endif
                </td>
                <td class="report-info" width="40%" align="right">
                    <h2>Investor Ledger Report</h2>
                    @if($investorName)
                    <p><strong>Investor:</strong> {{ $investorName }}</p>
                    @else
                    <p><strong>Investor:</strong> All Investors</p>
                    @endif
                    <p><strong>Period:</strong> {{ $dateFrom ?: 'All Time' }} to {{ $dateTo ?: 'Present' }}</p>
                    <p><strong>Type:</strong> {{ $type ?: 'All Transactions' }}</p>
                    <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Investor</th>
                <th>Type</th>
                <th>Description</th>
                <th class="text-right">Amount IN (+)</th>
                <th class="text-right">Amount OUT (-)</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->date->format('Y-m-d H:i') }}</td>
                    <td>{{ $entry->investor->name }}</td>
                    <td>{{ str_replace('_', ' ', strtoupper($entry->transaction_type)) }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="text-right text-emerald">
                        @if($entry->profit_credit > 0 || $entry->purchase_credit > 0)
                            Rs {{ number_format($entry->profit_credit + $entry->purchase_credit, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right text-rose">
                        @if($entry->profit_debit > 0 || $entry->purchase_debit > 0)
                            Rs {{ number_format($entry->profit_debit + $entry->purchase_debit, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">Rs {{ number_format($entry->total_payable_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No ledger entries found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a system generated report. No signature required.</p>
    </div>

</body>
</html>
