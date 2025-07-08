<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice #{{ $sale->reference_no }}</title>
    <!-- Remove the entire <style>...</style> block. Use only Bootstrap classes and theme variables for all table, text, and background styling. -->
</head>
<body>
    <div class="no-print" style="padding: 20px; background: #f8f9fa; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; margin-left: 10px; cursor: pointer;">
            Close
        </button>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-name">Stock360 Inventory System</div>
            <div class="company-details">123 Business Avenue, Addis Ababa, Ethiopia</div>
            <div class="company-details">Phone: +251 912-345-678 | Email: info@stock360.com</div>
            
            <div class="invoice-title">SALES INVOICE</div>
            <div style="font-size: 16px; color: #7f8c8d;">Reference #: {{ $sale->reference_no }}</div>
        </div>

        <div class="invoice-details">
            <div class="invoice-details-col">
                <div class="invoice-details-title">BILL TO:</div>
                <div style="font-size: 18px; font-weight: bold;">{{ $sale->customer->name }}</div>
                <div class="customer-address">
                    {{ $sale->customer->address ?? 'No address provided' }}<br>
                    Phone: {{ $sale->customer->phone ?? 'N/A' }}<br>
                    Email: {{ $sale->customer->email ?? 'N/A' }}
                </div>
            </div>
            <div class="invoice-details-col">
                <div class="invoice-details-title">INVOICE DETAILS:</div>
                <table style="border: none; width: 100%;">
                    <tr>
                        <td style="border: none; padding: 5px 0;">Invoice Date:</td>
                        <td style="border: none; padding: 5px 0; text-align: right;">{{ $sale->sale_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 5px 0;">Sale Status:</td>
                        <td style="border: none; padding: 5px 0; text-align: right;">
                            <span style="font-weight: bold; color: #27ae60;">{{ ucfirst($sale->status) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 5px 0;">Payment Status:</td>
                        <td style="border: none; padding: 5px 0; text-align: right;">
                            @if($sale->payment_status === 'paid')
                                <span style="font-weight: bold; color: #27ae60;">Paid</span>
                            @elseif($sale->payment_status === 'partial')
                                <span style="font-weight: bold; color: #2980b9;">Partially Paid</span>
                            @else
                                <span style="font-weight: bold; color: #f39c12;">Pending</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div style="font-weight: bold;">{{ $item->item->name }}</div>
                                <div style="font-size: 13px; color: #7f8c8d;">SKU: {{ $item->item->sku }}</div>
                            </td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->item->unit }}</td>
                            <td class="text-right amount">ETB {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right amount">ETB {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="totals">
            <div class="totals-row">
                <div>Subtotal:</div>
                <div class="amount">ETB {{ number_format($sale->total_amount - ($sale->tax + $sale->shipping - $sale->discount), 2) }}</div>
            </div>
            @if($sale->discount > 0)
            <div class="totals-row">
                <div>Discount:</div>
                <div class="amount">ETB {{ number_format($sale->discount, 2) }}</div>
            </div>
            @endif
            @if($sale->tax > 0)
            <div class="totals-row">
                <div>Tax (15%):</div>
                <div class="amount">ETB {{ number_format($sale->tax, 2) }}</div>
            </div>
            @endif
            @if($sale->shipping > 0)
            <div class="totals-row">
                <div>Shipping:</div>
                <div class="amount">ETB {{ number_format($sale->shipping, 2) }}</div>
            </div>
            @endif
            <div class="totals-row final">
                <div>Total Amount:</div>
                <div class="amount">ETB {{ number_format($sale->total_amount, 2) }}</div>
            </div>
            <div class="totals-row">
                <div>Amount Paid:</div>
                <div class="amount">ETB {{ number_format($sale->paid_amount, 2) }}</div>
            </div>
            @if($sale->due_amount > 0)
            <div class="totals-row" style="font-weight: bold; color: #e74c3c;">
                <div>Balance Due:</div>
                <div class="amount">ETB {{ number_format($sale->due_amount, 2) }}</div>
            </div>
            @endif
        </div>

        <div class="payment-info">
            <div class="payment-title">PAYMENT INFORMATION</div>
            @if($sale->payments->count() > 0)
                <table style="width: 100%; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td>{{ ucfirst($payment->payment_method) }}</td>
                                <td>{{ $payment->reference_no ?? 'N/A' }}</td>
                                <td class="text-right amount">ETB {{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No payment records found.</p>
            @endif
            
            <div style="margin-top: 10px;">
                <div>Payment Status: 
                    @if($sale->payment_status === 'paid')
                        <span class="payment-status paid">PAID</span>
                    @elseif($sale->payment_status === 'partial')
                        <span class="payment-status partial">PARTIALLY PAID</span>
                    @else
                        <span class="payment-status pending">PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature">
                <div>Prepared By</div>
                <div style="font-weight: bold; margin-top: 10px;">{{ $sale->user->name }}</div>
            </div>
            <div class="signature">
                <div>Received By</div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>&copy; {{ date('Y') }} Stock360 Inventory System. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 