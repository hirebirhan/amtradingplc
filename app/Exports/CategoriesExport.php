<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'ID',
            'Name',
            'Description',
            'Parent Category',
            'Items Count',
            'Subcategories Count',
            'Status',
            'Created At'
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->description ?: 'N/A',
            $category->parent ? $category->parent->name : 'None',
            $category->items_count,
            $category->children_count,
            $category->is_active ? 'Active' : 'Inactive',
            $category->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }
} 