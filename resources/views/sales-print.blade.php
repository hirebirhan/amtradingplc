<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->reference_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 11px; margin: 0; padding: 0; }
            .page-break { page-break-before: always; }
            @page { size: A4; margin: 0.5in; }
            .card { box-shadow: none !important; border: none !important; }
            .container-fluid { padding: 0 !important; }
            .card-body { padding: 1rem !important; }
            .mb-4 { margin-bottom: 1rem !important; }
            .mb-3 { margin-bottom: 0.75rem !important; }
            .mb-2 { margin-bottom: 0.5rem !important; }
            .mt-5 { margin-top: 1rem !important; }
            .pt-4 { padding-top: 0.5rem !important; }
            .p-3 { padding: 0.5rem !important; }
            h2 { font-size: 1.25rem !important; }
            h3 { font-size: 1.1rem !important; }
            .fs-6 { font-size: 0.9rem !important; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Print Controls -->
    <div class="no-print container-fluid py-3 bg-white border-bottom">
        <div class="d-flex justify-content-center gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
    </div>

    <!-- Invoice Container -->
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Header -->
                        <div class="row align-items-center mb-2">
                            <div class="col-12 col-md-2 text-center text-md-start mb-2 mb-md-0">
                                <div class="bg-primary text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-building fs-4"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-8 text-center mb-2 mb-md-0">
                                <h2 class="fw-bold text-primary mb-1">AM Trading PLC</h2>
                                <p class="text-muted mb-1 fw-medium small">SMART SOLUTIONS WITH GENUINE CARE</p>
                                <div class="small text-muted">
                                    <div>Tel: +251-11-26 76 26 / 011-11-22 37 | Email: AMTradingPLC@gmail.com</div>
                                    <div>Habte Giorgis, Tefera Business Center</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-2 text-center text-md-end">
                                <div class="border-start border-primary border-2 ps-2 d-inline-block">
                                    <div class="fw-bold small">Date</div>
                                    <div class="text-muted small">{{ $sale->sale_date->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Title -->
                        <div class="text-center mb-2">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="text-center flex-grow-1">
                                    <h2 class="text-primary mb-0 fw-bold" style="font-size: 1.8rem;">የሽያጭ መጠየቂያ</h2>
                                    <h3 class="fw-bold text-decoration-underline">SALES INVOICE</h3>
                                </div>
                                <div class="badge bg-primary fs-6 px-2 py-1">
                                    No: {{ $sale->reference_no }}
                                </div>
                            </div>
                        </div>

                        <!-- Customer & Invoice Info -->
                        <div class="row mb-2">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="border rounded p-2 h-100">
                                    <h6 class="fw-bold text-primary mb-1 small">Bill To:</h6>
                                    <div class="fw-bold small">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    @if($sale->customer)
                                        @if($sale->customer->phone)
                                            <div class="text-muted small"><i class="bi bi-telephone"></i> {{ $sale->customer->phone }}</div>
                                        @endif
                                        @if($sale->customer->email)
                                            <div class="text-muted small"><i class="bi bi-envelope"></i> {{ $sale->customer->email }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <h6 class="fw-bold text-primary mb-1 small">Invoice Details:</h6>
                                    <div class="row g-1 small">
                                        <div class="col-5"><strong>Date:</strong></div>
                                        <div class="col-7">{{ $sale->sale_date->format('M d, Y') }}</div>
                                        <div class="col-5"><strong>Status:</strong></div>
                                        <div class="col-7">
                                            <span class="badge bg-{{ $sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger') }} small">
                                                {{ ucfirst(str_replace('_', ' ', $sale->payment_status)) }}
                                            </span>
                                        </div>
                                        <div class="col-5"><strong>Payment:</strong></div>
                                        <div class="col-7">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</div>
                                        @if($sale->warehouse)
                                            <div class="col-5"><strong>Warehouse:</strong></div>
                                            <div class="col-7">{{ $sale->warehouse->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mb-2">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center fw-bold small">S.N</th>
                                        <th class="text-center fw-bold small">Description</th>
                                        <th class="text-center fw-bold small">Unit</th>
                                        <th class="text-center fw-bold small">Qty</th>
                                        <th class="text-center fw-bold small">Unit Price</th>
                                        <th class="text-center fw-bold small">Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $subtotal = 0; @endphp
                                    @foreach($sale->items as $index => $item)
                                        @php
                                            $itemTotal = $item->quantity * $item->unit_price;
                                            $subtotal += $itemTotal;
                                        @endphp
                                        <tr>
                                            <td class="text-center fw-medium small">{{ $index + 1 }}</td>
                                            <td class="small">
                                                {{ $item->item->name }}
                                                @if($item->item->sku)
                                                    <br><small class="text-muted">SKU: {{ $item->item->sku }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center small">{{ strtoupper($item->item->unit->name ?? 'PCS') }}</td>
                                            <td class="text-center fw-medium small">{{ number_format($item->quantity, is_int($item->quantity) ? 0 : 2) }}</td>
                                            <td class="text-end fw-medium small">ETB {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end fw-bold small">ETB {{ number_format($itemTotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Sub Total:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                    @if($sale->tax_amount > 0)
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Tax:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($sale->tax_amount, 2) }}</td>
                                    </tr>
                                    @endif
                                    @if($sale->shipping_amount > 0)
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Shipping:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($sale->shipping_amount, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Grand Total:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($sale->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Amount Paid:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($sale->paid_amount, 2) }}</td>
                                    </tr>
                                    @if($sale->due_amount > 0)
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold small">Amount Due:</td>
                                        <td class="text-end fw-bold small">ETB {{ number_format($sale->due_amount, 2) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Payment Information -->
                        @if(in_array($sale->payment_method, ['telebirr', 'bank_transfer']) && ($sale->transaction_number || $sale->bankAccount))
                        <div class="alert alert-info mb-2 py-2">
                            <h6 class="alert-heading small mb-1"><i class="bi bi-credit-card"></i> Payment Information</h6>
                            @if($sale->transaction_number)
                                <div class="small"><strong>Transaction Number:</strong> {{ $sale->transaction_number }}</div>
                            @endif
                            @if($sale->payment_method === 'bank_transfer' && $sale->bankAccount)
                                <div class="small"><strong>Bank Account:</strong> {{ $sale->bankAccount->account_name }}</div>
                            @endif
                        </div>
                        @endif

                        <!-- Signatures -->
                        <div class="row mt-3 pt-2">
                            <div class="col-12 col-md-6 text-center mb-2 mb-md-0">
                                <div class="border-top border-2 pt-2 mx-3">
                                    <div class="fw-bold small">{{ $sale->user->name ?? 'Sales Representative' }}</div>
                                    <small class="text-muted">Prepared By / Authorized Representative</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 text-center">
                                <div class="border-top border-2 pt-2 mx-3">
                                    <div class="fw-bold small">Customer Signature</div>
                                    <small class="text-muted">I agree to the terms and conditions</small>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-3 pt-2 border-top">
                            <div class="text-muted small">
                                <div class="mb-1">
                                    <i class="bi bi-info-circle"></i> 
                                    This is a computer-generated document. No physical signature is required.
                                </div>
                                <div>&copy; {{ date('Y') }} AM Trading PLC. All rights reserved.</div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>