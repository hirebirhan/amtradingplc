<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma - {{ $proforma->reference_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; }
            .container { max-width: 100% !important; }
            .table { font-size: 9px; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .table-responsive { font-size: 11px; }
            .company-name { font-size: 16px !important; }
            .proforma-title h2 { font-size: 14px !important; }
            .table th, .table td { padding: 4px 2px; font-size: 10px; }
        }
        
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 15px;
            background: white;
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 21cm;
                width: 21cm;
                min-height: 29.7cm;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                margin: 20px auto;
                border-radius: 5px;
            }
        }
        
        .print-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 20px;
            line-height: 1.4;
            color: #000;
            background-color: #f5f5f5;
        }
        
        .company-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
            color: #000;
        }
        
        .company-tagline {
            font-size: 11px;
            color: #000;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .contact-info {
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            font-weight: bold;
        }
        
        .proforma-title {
            text-align: center;
            margin: 15px 0;
        }
        
        .proforma-title h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .amharic-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .vat-toggle {
            display: none;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            padding: 8px 4px;
        }
        
        .table td {
            padding: 6px 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .totals-table {
            border: 1px solid #dee2e6;
        }
        
        .totals-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
        }
        
        .footer-notes {
            font-size: 11px;
            margin-top: 20px;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- VAT Toggle (No Print) -->
    <div class="vat-toggle no-print">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="includeVat" {{ $includeVat ? 'checked' : '' }}>
            <label class="form-check-label" for="includeVat">
                Include 15% VAT
            </label>
        </div>
    </div>

    <div class="container-fluid">
        <div class="container">
            
            <!-- Print Header Controls -->
            <div class="print-header no-print">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="form-check form-switch mb-2 mb-md-0">
                        <input class="form-check-input" type="checkbox" id="includeVat" onclick="toggleVAT(this)">
                        <label class="form-check-label" for="includeVat">
                            Include 15% VAT
                        </label>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary me-2 mb-2 mb-md-0">
                            <i class="fas fa-print"></i> Print Proforma
                        </button>
                        <a href="{{ route('admin.proformas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Proformas
                        </a>
                    </div>
                </div>
            </div>
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">MUHDIN GENERAL TRADING</div>
            <div class="company-tagline">SMART SOLUTIONS WITH GENUINE CARE</div>
            <div class="contact-info">
                <div class="mb-1">Tel: +251-11-26 76 26 / 011-11-22 37 / 011-11-39-10 / +251-9-11-26-75-26</div>
                <div class="mb-1">Email: muhdintrading@gmail.com</div>
                <div>Addis Ababa, Ethiopia</div>
            </div>
        </div>

        <!-- Proforma Title -->
        <div class="proforma-title">
            <div class="amharic-title">ዋጋ ማቅረቢያ</div>
            <h2>PROFORMA</h2>
        </div>

        <!-- Proforma Details -->
        <div class="row mb-3">
            <div class="col-md-6 col-12">
                <div class="mb-2"><strong>No:</strong> {{ $proforma->reference_no }}</div>
                <div class="mb-2"><strong>To:</strong> {{ $proforma->customer->name }}</div>
                @if($proforma->contact_person)
                    <div class="mb-2"><strong>Contact:</strong> {{ $proforma->contact_person }}</div>
                @endif
                @if($proforma->contact_phone)
                    <div class="mb-2"><strong>Phone:</strong> {{ $proforma->contact_phone }}</div>
                @endif
            </div>
            <div class="col-md-6 col-12 text-md-end">
                <div class="mb-2"><strong>Date:</strong> {{ $proforma->proforma_date ? $proforma->proforma_date->format('d/m/Y') : $proforma->created_at->format('d/m/Y') }}</div>
                @if($proforma->valid_until)
                    <div class="mb-2"><strong>Valid Until:</strong> {{ $proforma->valid_until->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>

        <!-- Items Table with Totals -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th style="width: 5%;">S.N</th>
                        <th style="width: 40%;">Description</th>
                        <th style="width: 8%;">Unit</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 15%;">Unit Price</th>
                        <th style="width: 15%;">Total Price</th>
                        <th style="width: 9%;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proforma->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->item->name }}</td>
                        <td class="text-center">PCS</td>
                        <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                        <td></td>
                    </tr>
                    @endforeach
                    <!-- Totals Section -->
                    <tr>
                        <td colspan="5" class="text-end"><strong>Sub Total:</strong></td>
                        <td class="text-end"><strong>{{ number_format($proforma->total_amount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                    <tr id="vatRow" style="display: none;">
                        <td colspan="5" class="text-end"><strong>VAT (15%):</strong></td>
                        <td class="text-end"><strong id="vatAmount">0.00</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end"><strong>Grand Total:</strong></td>
                        <td class="text-end"><strong id="grandTotal">{{ number_format($proforma->total_amount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- VAT Note -->
        <div class="footer-notes">
            <p class="mb-2">
                <strong>Note:</strong> 
                <span id="vatNoteText">
                    {{ $includeVat ? 'ዋጋው 15% ቫት ያካተተ ነው / The above price includes 15% VAT' : 'ዋጋው ቫት አያካትትም / The above price does not include VAT' }}
                </span>
            </p>
            
            <p class="mb-2">ለአስራር እንደመቸን ከአንድ ቀን በፊት ቀድመው ያሳውቁን::</p>
            
            <p class="mb-2">አሸናፊ ከሆንን ቸኩ (ማቡስ ቢዝነስ) Muhdin Trading በሚል ይዘጋጅ</p>
            
            @if($proforma->valid_until)
                <p class="mb-2">ይህ የዋጋ ማቅረቢያ የሚያገለግለው ለ{{ $proforma->valid_until->diffInDays($proforma->proforma_date ?? $proforma->created_at) }} ቀን ብቻ ነው ::</p>
                <p class="mb-2">This Proforma is valid for {{ $proforma->valid_until->diffInDays($proforma->proforma_date ?? $proforma->created_at) }} days only.</p>
            @else
                <p class="mb-2">ይህ የዋጋ ማቅረቢያ የሚያገለግለው ለ_______ ቀን ብቻ ነው ::</p>
                <p class="mb-2">This Proforma is valid for _______ days only.</p>
            @endif
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="row">
                <div class="col-md-6 col-12">
                    <p class="mb-1">ፊርማ : _____________________</p>
                    <p class="mb-3">Signature : _____________________</p>
                </div>
                <div class="col-md-6 col-12">
                    <p class="mb-1">ቀን : _____________________</p>
                    <p class="mb-3">Date : _____________________</p>
                </div>
            </div>
                </div>
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        const subtotal = {{ $proforma->total_amount }};
        
        function toggleVAT(checkbox) {
            const vatRow = document.getElementById('vatRow');
            const vatAmount = document.getElementById('vatAmount');
            const grandTotal = document.getElementById('grandTotal');
            const vatNote = document.getElementById('vatNoteText');
            
            if (checkbox.checked) {
                // Show VAT
                vatRow.style.display = 'table-row';
                vatRow.classList.remove('d-none');
                vatAmount.innerHTML = (subtotal * 0.15).toFixed(2);
                grandTotal.innerHTML = (subtotal * 1.15).toFixed(2);
                if (vatNote) {
                    vatNote.innerHTML = 'ዋጋው 15% ቫት ያካተተ ነው / The above price includes 15% VAT';
                }
            } else {
                // Hide VAT
                vatRow.style.display = 'none';
                vatRow.classList.add('d-none');
                grandTotal.innerHTML = subtotal.toFixed(2);
                if (vatNote) {
                    vatNote.innerHTML = 'ዋጋው ቫት አያካትትም / The above price does not include VAT';
                }
            }
        }
        
        // Initialize on page load
        window.onload = function() {
            const vatRow = document.getElementById('vatRow');
            const grandTotal = document.getElementById('grandTotal');
            const vatNote = document.getElementById('vatNoteText');
            
            // Start with VAT hidden
            vatRow.style.display = 'none';
            grandTotal.innerHTML = subtotal.toFixed(2);
            if (vatNote) {
                vatNote.innerHTML = 'ዋጋው ቫት አያካትትም / The above price does not include VAT';
            }
        };
    </script>
</body>
</html>