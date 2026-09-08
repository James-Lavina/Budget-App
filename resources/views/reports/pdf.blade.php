<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 90px 40px 70px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        /* ---------- Fixed header, repeats on every page ---------- */
        header {
            position: fixed;
            top: -70px;
            left: 0px;
            right: 0px;
            height: 70px;
        }

        .brand-bar {
            height: 4px;
            background-color: {{ $primaryColor }};
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
        }

        .header-table td {
            vertical-align: middle;
        }

        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #64748b;
        }

        /* ---------- Fixed footer, repeats on every page ---------- */
        footer {
            position: fixed;
            bottom: -55px;
            left: 0px;
            right: 0px;
            height: 40px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        footer .footer-table {
            width: 100%;
        }

        footer .page-num:after {
            content: counter(page);
        }

        footer .page-count:after {
            content: counter(pages);
        }

        /* ---------- Title block ---------- */
        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        .report-range {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .filter-chip {
            display: inline-block;
            background-color: #eef2ff;
            color: {{ $primaryColor }};
            font-size: 8px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 3px;
            margin-top: 6px;
            margin-right: 4px;
        }

        /* ---------- Summary metric cards ---------- */
        .summary-table {
            width: 100%;
            margin-top: 16px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }

        .summary-cell {
            width: 25%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
        }

        .summary-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        /* ---------- Category breakdown ---------- */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 22px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .cat-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cat-table td {
            padding: 5px 0;
            font-size: 9px;
            vertical-align: middle;
        }

        .cat-dot {
            width: 8px;
            height: 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .cat-name-cell {
            width: 28%;
        }

        .cat-bar-cell {
            width: 52%;
        }

        .cat-bar-track {
            background-color: #f1f5f9;
            height: 8px;
            border-radius: 4px;
            width: 100%;
        }

        .cat-bar-fill {
            height: 8px;
            border-radius: 4px;
        }

        .cat-amount-cell {
            width: 20%;
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        /* ---------- Expense line items ---------- */
        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .expense-table thead th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
        }

        .expense-table thead th.amount-col {
            text-align: right;
        }

        .expense-table tbody td {
            padding: 6px 8px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
        }

        .expense-table tbody tr.alt {
            background-color: #f8fafc;
        }

        .expense-table td.amount-col {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        .expense-table tfoot td {
            padding: 8px;
            font-size: 9px;
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }

        .expense-table tfoot td.amount-col {
            text-align: right;
        }

        .empty-state {
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
            padding: 24px 0;
        }
    </style>
</head>
<body>

    <header>
        <div class="brand-bar"></div>
        <table class="header-table">
            <tr>
                @if($logoDataUri)
                    <td style="width: 36px;">
                        <img src="{{ $logoDataUri }}" style="height: 32px; width: 32px; object-fit: cover; border-radius: 4px;">
                    </td>
                @endif
                <td>
                    <div class="brand-name">{{ $applicationName }}</div>
                    <div class="brand-sub">Behavior-Guided Financial Management — Admin Report</div>
                </td>
                <td class="header-meta">
                    Generated {{ now()->format('M j, Y \a\t g:i A') }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td>{{ $applicationName }} Financial Report</td>
                <td style="text-align: right;">
                    Page <span class="page-num"></span> of <span class="page-count"></span>
                </td>
            </tr>
        </table>
    </footer>

    <div class="report-title">Expense Report</div>
    <div class="report-range">
        {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
    </div>

    @if($filterUser || $filterCategory)
        <div>
            @if($filterUser)
                <span class="filter-chip">Student: {{ $filterUser->name }}</span>
            @endif
            @if($filterCategory)
                <span class="filter-chip">Category: {{ $filterCategory->name }}</span>
            @endif
        </div>
    @endif

    {{-- Summary metric cards --}}
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Expenses</div>
                <div class="summary-value">{{ number_format($summary['total_expenses']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">₱{{ number_format($summary['total_amount'], 2) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Budget Alerts</div>
                <div class="summary-value">{{ number_format($summary['budget_alerts']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">OCR Scans</div>
                <div class="summary-value">{{ number_format($summary['ocr_scans']) }}</div>
            </td>
        </tr>
    </table>

    {{-- Category breakdown --}}
    @if($categoryBreakdown->isNotEmpty())
        <div class="section-title">Spending by Category</div>
        <table class="cat-table">
            @foreach($categoryBreakdown as $cat)
                @php
                    $pct = $categoryGrandTotal > 0 ? round(($cat['total'] / $categoryGrandTotal) * 100) : 0;
                @endphp
                <tr>
                    <td class="cat-name-cell">
                        <span class="cat-dot" style="background-color: {{ $cat['color'] }};"></span>
                        {{ $cat['name'] }}
                    </td>
                    <td class="cat-bar-cell">
                        <div class="cat-bar-track">
                            <div class="cat-bar-fill" style="width: {{ max(2, $pct) }}%; background-color: {{ $cat['color'] }};"></div>
                        </div>
                    </td>
                    <td class="cat-amount-cell">
                        ₱{{ number_format($cat['total'], 2) }} <span style="color: #94a3b8; font-weight: normal;">({{ $pct }}%)</span>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Itemized expense list --}}
    <div class="section-title">Transaction Detail</div>

    @if($expenses->isEmpty())
        <div class="empty-state">No expenses recorded for this date range and filters.</div>
    @else
        <table class="expense-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 22%;">Student</th>
                    <th style="width: 18%;">Category</th>
                    <th style="width: 33%;">Item</th>
                    <th class="amount-col" style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $i => $expense)
                    <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                        <td>{{ $expense->transaction_date->format('M j, Y') }}</td>
                        <td>{{ $expense->user->name ?? 'Unknown' }}</td>
                        <td>{{ $expense->category->name ?? 'Uncategorized' }}</td>
                        <td>{{ $expense->item_name }}</td>
                        <td class="amount-col">₱{{ number_format($expense->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Total</td>
                    <td class="amount-col">₱{{ number_format($expenses->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

</body>
</html>