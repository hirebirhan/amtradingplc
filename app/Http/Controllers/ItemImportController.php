<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ItemImportController extends Controller
{
    /**
     * Download a sample template for importing items
     *
     * @return Response
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers with all required fields
        $headers = [
            'A1' => 'Name*',
            'B1' => 'SKU',
            'C1' => 'Barcode',
            'D1' => 'Category',
            'E1' => 'Unit',
            'F1' => 'Unit Quantity',
            'G1' => 'Cost Price (ETB)',
            'H1' => 'Selling Price (ETB)',
            'I1' => 'Reorder Level',
            'J1' => 'Brand',
            'K1' => 'Description'
        ];

        // Set headers
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E86AB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Add example data
        $examples = [
            ['Laptop Dell XPS 13', 'LAP-DELL-001', 'BAR-LAP001', 'Electronics', 'pcs', 1, 45000.00, 55000.00, 5, 'Dell', 'High-performance laptop for business use'],
            ['Coffee Beans Arabica', 'COF-ARAB-001', 'BAR-COF001', 'Beverages', 'kg', 1, 250.00, 350.00, 20, 'Arabica Premium', 'Premium coffee beans from Ethiopia'],
            ['Notebook A4 Size', 'NOTE-A4-001', 'BAR-NOTE001', 'Stationery', 'pack', 10, 150.00, 200.00, 15, 'OfficeMax', 'A4 size notebooks, 10 pieces per pack'],
            ['Cooking Oil 1L', 'OIL-COOK-001', 'BAR-OIL001', 'Food', 'bottle', 1, 120.00, 150.00, 30, 'Sunflower', 'Pure sunflower cooking oil'],
            ['T-Shirt Cotton M', 'TSH-COT-M', 'BAR-TSH001', 'Clothing', 'pcs', 1, 300.00, 450.00, 25, 'Cotton Comfort', 'Cotton t-shirt, medium size'],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->setCellValue('A' . $row, $example[0]);
            $sheet->setCellValue('B' . $row, $example[1]);
            $sheet->setCellValue('C' . $row, $example[2]);
            $sheet->setCellValue('D' . $row, $example[3]);
            $sheet->setCellValue('E' . $row, $example[4]);
            $sheet->setCellValue('F' . $row, $example[5]);
            $sheet->setCellValue('G' . $row, $example[6]);
            $sheet->setCellValue('H' . $row, $example[7]);
            $sheet->setCellValue('I' . $row, $example[8]);
            $sheet->setCellValue('J' . $row, $example[9]);
            $sheet->setCellValue('K' . $row, $example[10]);
            $row++;
        }

        // Style the data rows
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A2:K' . ($row - 1))->applyFromArray($dataStyle);

        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');
        
        // Add default values note
        $instructionsSheet->setCellValue('A1', 'IMPORTANT: Default Values');
        $instructionsSheet->setCellValue('A2', '• All items will be imported as ACTIVE');
        $instructionsSheet->setCellValue('A3', '• Empty prices will default to 0');
        $instructionsSheet->setCellValue('A4', '• Empty categories can use default from import page');
        $instructionsSheet->setCellValue('A5', '• Empty units will default to "pcs"');
        $instructionsSheet->setCellValue('A6', '• Empty unit quantities will default to 1');
        $instructionsSheet->setCellValue('A7', '• Empty reorder levels will default to 10');
        $instructionsSheet->setCellValue('A8', '• SKU and Barcode will be auto-generated if empty');
        
        // Style the default values section
        $defaultStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
        ];
        $instructionsSheet->getStyle('A1')->applyFromArray($defaultStyle);
        
        $noteStyle = [
            'font' => [
                'color' => ['rgb' => '6C757D'],
            ],
        ];
        $instructionsSheet->getStyle('A2:A8')->applyFromArray($noteStyle);
        
        // Add spacing
        $instructionsSheet->setCellValue('A10', '');
        
        $instructions = [
            ['Field', 'Required', 'Description', 'Example'],
            ['Name*', 'Yes', 'Item name (max 255 characters)', 'Laptop Dell XPS 13'],
            ['SKU', 'No', 'Stock Keeping Unit (auto-generated if empty)', 'LAP-DELL-001'],
            ['Barcode', 'No', 'Product barcode (auto-generated if empty)', 'BAR-LAP001'],
            ['Category', 'No', 'Item category (can use default from import page)', 'Electronics'],
            ['Unit', 'No', 'Unit of measurement (default: pcs)', 'pcs, kg, box, pack'],
            ['Unit Quantity', 'No', 'Items per unit (default: 1)', '10 (for pack of 10)'],
            ['Cost Price (ETB)', 'No', 'Purchase cost in ETB (default: 0)', '45000.00'],
            ['Selling Price (ETB)', 'No', 'Selling price in ETB (default: 0)', '55000.00'],
            ['Reorder Level', 'No', 'Minimum stock level (default: 10)', '5'],
            ['Brand', 'No', 'Product brand (max 255 characters)', 'Dell'],
            ['Description', 'No', 'Item description (max 1000 characters)', 'High-performance laptop'],
        ];

        $instructionRow = 11; // Start after the default values section
        foreach ($instructions as $instruction) {
            $instructionsSheet->setCellValue('A' . $instructionRow, $instruction[0]);
            $instructionsSheet->setCellValue('B' . $instructionRow, $instruction[1]);
            $instructionsSheet->setCellValue('C' . $instructionRow, $instruction[2]);
            $instructionsSheet->setCellValue('D' . $instructionRow, $instruction[3]);
            $instructionRow++;
        }

        // Style instructions
        $instructionsSheet->getStyle('A11:D11')->applyFromArray($headerStyle);
        $instructionsSheet->getStyle('A12:D' . ($instructionRow - 1))->applyFromArray($dataStyle);
        
        foreach (range('A', 'D') as $column) {
            $instructionsSheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set the first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Create the file
        $writer = new Xlsx($spreadsheet);

        // Create a temporary file
        $fileName = 'items_import_template.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Save the file
        $writer->save($tempFile);

        // Return the file as a response
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}