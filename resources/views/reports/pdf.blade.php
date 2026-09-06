<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td, th { padding: 8px; border: 1px solid #e2e8f0; text-align: left; }
    </style>
</head>
<body>
    <h1>BudgetWise Report</h1>
    <p>{{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}</p>

    <table>
        <tr><th>Total Expenses</th><td>{{ $summary['total_expenses'] }}</td></tr>
        <tr><th>Total Amount</th><td>₱{{ number_format($summary['total_amount'], 2) }}</td></tr>
        <tr><th>Budget Alerts</th><td>{{ $summary['budget_alerts'] }}</td></tr>
        <tr><th>OCR Scans</th><td>{{ $summary['ocr_scans'] }}</td></tr>
    </table>
</body>
</html>