<?php

namespace App\Exports;

use App\Models\Credit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CreditsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Reference No',
            'Type',
            'Customer/Supplier',
            'Amount',
            'Paid Amount',
            'Balance',
            'Status',
            'Credit Date',
            'Due Date',
            'Created By',
            'Branch',
            'Description',
            'Reference Type',
            'Created At'
        ];
    }

    public function map($credit): array
    {
        $partyName = $credit->credit_type === 'receivable' 
            ? ($credit->customer?->name ?? '')
            : ($credit->supplier?->name ?? '');

        return [
            $credit->reference_no ?? '',
            ucfirst($credit->credit_type ?? ''),
            $partyName,
            $credit->amount ?? 0,
            $credit->paid_amount ?? 0,
            $credit->balance ?? 0,
            ucfirst($credit->status ?? ''),
            $credit->credit_date?->format('Y-m-d') ?? '',
            $credit->due_date?->format('Y-m-d') ?? '',
            $credit->user?->name ?? '',
            $credit->branch?->name ?? '',
            $credit->description ?? '',
            $credit->reference_type ?? '',
            $credit->created_at?->format('Y-m-d H:i:s') ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
            'D:F' => [
                'numberFormat' => [
                    'formatCode' => '#,##0.00'
                ]
            ],
        ];
    }
} 