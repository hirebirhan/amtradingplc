<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemImportController extends Controller
{
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1: Branch Names
        $branches = Branch::all();
        $currentCol = 12; // Starting after core item columns (A-K)
        
        $sheet->setCellValue('A1', 'Core Item Information');
        
        foreach ($branches as $branch) {
            $sheet->setCellValueByColumnAndRow($currentCol, 1, $branch->name);
            $currentCol++;
        }

        // Row 2: Item Headers
        $headers = [
            'Name', 'SKU', 'Barcode', 'Description', 'Category', 
            'Brand', 'Unit', 'UM', 'Cost', 'Price', 'Reorder Level'
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 2, $header);
        }

        // Branch quantity headers
        $currentCol = 12;
        foreach ($branches as $branch) {
            $sheet->setCellValueByColumnAndRow($currentCol, 2, 'Quantity');
            $currentCol++;
        }

        // Add a sample row
        $sheet->setCellValue('A3', 'Sample Item');
        $sheet->setCellValue('B3', 'SAMPLE-001');
        $sheet->setCellValue('C3', '123456789');
        $sheet->setCellValue('D3', 'This is a sample item description');
        $sheet->setCellValue('E3', 'General');
        $sheet->setCellValue('F3', 'Generic');
        $sheet->setCellValue('G3', 'pcs');
        $sheet->setCellValue('H3', '1');
        $sheet->setCellValue('I3', '100');
        $sheet->setCellValue('J3', '150');
        $sheet->setCellValue('K3', '10');
        
        // Sample quantities
        if ($branches->count() > 0) {
            $sheet->setCellValueByColumnAndRow(12, 3, '50');
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'item_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
