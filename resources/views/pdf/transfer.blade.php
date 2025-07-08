<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer #{{ $transfer->reference_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid var(--bs-border-color, #ddd);
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: var(--bs-light, #f2f2f2);
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        .summary {
            margin-top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .text-right {
            text-align: right;
        }
        .status {
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            color: var(--bs-warning, #f59e0b);
        }
        .status-completed {
            color: var(--bs-success, #10b981);
        }
        .status-canceled {
            color: var(--bs-danger, #ef4444);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>STOCK TRANSFER</h1>
        <p><strong>Reference:</strong> {{ $transfer->reference_no }}</p>
        <p><strong>Date:</strong> {{ $transfer->date->format('M d, Y') }}</p>
        <p><strong>Status:</strong>
            <span class="status status-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span>
        </p>
    </div>

    <div class="info-section">
        <h3>Transfer Information</h3>
        <table>
            <tr>
                <th>From Warehouse</th>
                <td>{{ $transfer->fromWarehouse->name }}</td>
                <th>To Warehouse</th>
                <td>{{ $transfer->toWarehouse->name }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>{{ $transfer->user->name }}</td>
                <th>Created On</th>
                <td>{{ $transfer->created_at->format('M d, Y H:i') }}</td>
            </tr>
            <tr>
                <th>Notes</th>
                <td colspan="3">{{ $transfer->notes ?? 'No notes provided' }}</td>
            </tr>
        </table>
    </div>

    <h3>Transfer Items</h3>
    @if($items->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Code</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->item->code }}</td>
                    <td>{{ $item->quantity }} {{ $item->item->unit }}</td>
                    <td>{{ number_format($item->unit_cost, 2) }}</td>
                    <td>{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Totals:</th>
                    <th>{{ $items->sum('quantity') }}</th>
                    <th></th>
                    <th>{{ number_format($items->sum(function($item) { return $item->quantity * $item->unit_cost; }), 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    @else
        <p>No items in this transfer</p>
    @endif

    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-item">
            <span>Total Items:</span>
            <span>{{ $items->count() }}</span>
        </div>
        <div class="summary-item">
            <span>Total Quantity:</span>
            <span>{{ $items->sum('quantity') }}</span>
        </div>
        <div class="summary-item">
            <span>Total Amount:</span>
            <span>{{ number_format($items->sum(function($item) { return $item->quantity * $item->unit_cost; }), 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <p>This document was generated on {{ now()->format('F d, Y H:i:s') }}</p>
        <p>Stock360 - Inventory Management System</p>
    </div>
</body>
</html>