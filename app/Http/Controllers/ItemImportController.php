<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Item;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;

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

    /**
     * Preview an import file: either uploaded or default amtradingstock.xlsx
     */
    public function preview(Request $request)
    {
        try {
            $defaultCategoryId = (int)$request->input('default_category_id', 0);
            $path = null;

            if ($request->hasFile('file')) {
                $uploaded = $request->file('file');
                $path = $uploaded->getRealPath();
            } elseif ($request->boolean('use_default', false)) {
                $defaultPath = base_path('amtradingstock.xlsx');
                if (!file_exists($defaultPath)) {
                    return back()->withErrors(['file' => 'Default file amtradingstock.xlsx was not found at project root.']);
                }
                $path = $defaultPath;
            } else {
                return back()->withErrors(['file' => 'Please upload a file or choose the default file.']);
            }

            // Load spreadsheet read-only
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = (int) $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Skip the first row as it might be a title or empty row
            // Read the second row (row 2) as the actual header row with debug information
            $headers = [];
            
            Log::info('Reading header row from Excel (row 2)');
            
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                
                $cell = $sheet->getCellByColumnAndRow($col, 2); // Use row 2 instead of row 1
                $value = (string) $cell->getValue();
                
                $formattedValue = $cell->getFormattedValue();
                
                
                // Log detailed information about the header
                
                Log::debug("Header at column {$col}: raw value='{$value}', formatted value='{$formattedValue}'");
                // Store the header value
                
                $headers[] = trim($value);
            
            }
            
            
            // Log the full header row for debugging
            
            Log::info('Header row parsed from row 2', ['headers' => $headers]);

            
            // log headers 
            
            Log::info('Headers from row 2: ' . json_encode($headers));

            
            // Read sample data rows starting at row 3 (since row 2 is now headers)
            $sample = [];
            
            $endRow = min($highestRow, 12); // Read up to row 12 to get 10 rows of data
            for ($row = 3; $row <= $endRow; $row++) { // Start from row 3
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $rowData[] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                }
                // Skip entirely empty rows
                if (count(array_filter($rowData, fn($v) => $v !== null && $v !== '')) > 0) {
                    $sample[] = $rowData;
                }
            }

            // 
            Log::info('Sample data rows from row 3: ' . json_encode($sample));
            
            // Read all items for JSON format
            $allItems = $this->getAllItemsAsJson($sheet, $highestRow, $highestColumnIndex, $headers);

            // Basic mapping suggestions based on common headers
            $suggestions = $this->suggestMappings($headers);

            return view('items-import', [
                'defaultFileExists' => file_exists(base_path('amtradingstock.xlsx')),
                'categories' => \App\Models\Category::orderBy('name')->get(['id','name']),
                'default_category_id' => $defaultCategoryId,
                'preview' => [
                    'sheetTitle' => $sheet->getTitle(),
                    'rowCount' => $highestRow - 1, // excluding header
                    'headers' => $headers,
                    'sample' => $sample,
                    'suggestions' => $suggestions,
                    'allItems' => $allItems, // Add the full list of items
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Import preview failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['file' => 'Failed to read the spreadsheet: ' . $e->getMessage()]);
        }
    }

    /**
     * Placeholder for applying an import once mapping/business rules are confirmed.
     */
    public function apply(Request $request)
    {
        $defaultCategoryId = (int)$request->input('default_category_id', 0);
        $isJsonImport = $request->boolean('json_import', false);
        
        try {
            // Handle JSON-based import if it's set
            if ($isJsonImport && $request->has('item_data')) {
                return $this->processJsonImport($request);
            }
            
            // Traditional file-based import
            $path = null;
            if ($request->hasFile('file')) {
                $path = $request->file('file')->getRealPath();
            } elseif ($request->boolean('use_default', false)) {
                $defaultPath = base_path('amtradingstock.xlsx');
                if (!file_exists($defaultPath)) {
                    return back()->withErrors(['file' => 'Default file amtradingstock.xlsx was not found at project root.']);
                }
                $path = $defaultPath;
            } else {
                return back()->withErrors(['file' => 'Please upload a file or choose the default file.']);
            }

            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = (int)$sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Build header map
            $headers = [];
            for ($c=1; $c <= $highestColumnIndex; $c++) {
                $h = trim((string)$sheet->getCellByColumnAndRow($c, 1)->getValue());
                $headers[$c] = $h;
            }
            $findCol = function(array $candidates) use ($headers) {
                foreach ($headers as $idx => $h) {
                    $hn = strtolower(preg_replace('/[^a-z0-9]+/i','', $h));
                    foreach ($candidates as $cand) {
                        $cn = strtolower(preg_replace('/[^a-z0-9]+/i','', $cand));
                        if ($hn === $cn || str_contains($hn, $cn)) {
                            return $idx;
                        }
                    }
                }
                return null;
            };

            $colName = $findCol(['name','item','designation']);
            $colSku = $findCol(['code','sku']);
            $colBarcode = $findCol(['barcode']);
            $colCategory = $findCol(['category']);
            $colUM = $findCol(['um','u.m']);
            $colUCost = $findCol(['ucost','u.cost','unitcost','cost']);
            $colBicha = $findCol(['bicha']);
            $colKemer = $findCol(['kemer']);
            $colFuri = $findCol(['furi']);

            $created = 0; $updated = 0; $stockAdjusted = 0; $errors = [];
            $stockService = new StockMovementService();

            // Resolve branches by name
            $branchMap = [];
            $branchNames = ['bicha' => 'BICHA', 'kemer' => 'Kemer', 'furi' => 'Furi'];
            foreach ($branchNames as $key => $display) {
                $b = Branch::whereRaw('LOWER(name) = ?', [strtolower($display)])->first();
                if ($b) { $branchMap[$key] = $b->id; }
            }

            for ($row=2; $row <= $highestRow; $row++) {
                try {
                    $get = function($col) use ($sheet,$row) {
                        if (!$col) return null;
                        return $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    };

                    $name = trim((string)($get($colName) ?? ''));
                    if ($name === '') { continue; }

                    $sku = trim((string)($get($colSku) ?? ''));
                    $barcode = trim((string)($get($colBarcode) ?? ''));
                    $categoryName = trim((string)($get($colCategory) ?? ''));
                    $unitQuantity = (int)preg_replace('/[^0-9]/','', (string)($get($colUM) ?? '1'));
                    if ($unitQuantity <= 0) $unitQuantity = 1;
                    $costPrice = (float)str_replace([',',' '],['',''], (string)($get($colUCost) ?? '0'));
                    if ($costPrice < 0) $costPrice = 0;

                    // Find or default category
                    $categoryId = null;
                    if ($categoryName !== '') {
                        $cat = Category::whereRaw('LOWER(name)=?', [strtolower($categoryName)])->first();
                        if ($cat) { $categoryId = $cat->id; }
                    }
                    if (!$categoryId && $defaultCategoryId > 0) {
                        $categoryId = $defaultCategoryId;
                    }
                    if (!$categoryId) {
                        // fallback to first category if exists (optional)
                        $categoryId = Category::value('id');
                    }

                    // Upsert item: prefer SKU=code; barcode optional; fallback to name
                    $item = null;
                    if ($sku !== '') {
                        $item = Item::firstOrNew(['sku' => $sku]);
                    } elseif ($barcode !== '') {
                        $item = Item::firstOrNew(['barcode' => $barcode]);
                    } else {
                        $item = Item::firstOrNew(['name' => $name]);
                    }

                    $isNew = !$item->exists;
                    $item->name = $name;
                    if ($sku !== '') $item->sku = $sku;
                    if ($barcode !== '') $item->barcode = $barcode; // optional
                    if ($categoryId) $item->category_id = $categoryId;
                    $item->unit = $item->unit ?: 'pcs';
                    $item->unit_quantity = $unitQuantity;
                    $item->cost_price = round($costPrice, 2);
                    $item->selling_price = round($costPrice, 2); // selling = cost
                    $item->cost_price_per_unit = round($unitQuantity > 0 ? $costPrice / $unitQuantity : 0, 2);
                    $item->selling_price_per_unit = $item->cost_price_per_unit;
                    $item->is_active = true;
                    $item->save();
                    $isNew ? $created++ : $updated++;

                    // For each branch, set absolute quantity to sheet value
                    $branchQtys = [
                        'bicha' => (float)($get($colBicha) ?? 0),
                        'kemer' => (float)($get($colKemer) ?? 0),
                        'furi'  => (float)($get($colFuri) ?? 0),
                    ];

                    foreach ($branchQtys as $key => $qty) {
                        if (!isset($branchMap[$key])) continue; // branch not found
                        $qty = max(0, (float)$qty);
                        $branchId = $branchMap[$key];
                        // Ensure default warehouse for branch
                        $warehouse = $stockService->ensureBranchWarehouse($branchId);
                        // Fetch existing stock (we set absolute on default warehouse)
                        $stock = Stock::firstOrNew(['warehouse_id' => $warehouse->id, 'item_id' => $item->id]);
                        $old = (float)($stock->exists ? $stock->quantity : 0);
                        if ($old != $qty) {
                            $stock->quantity = $qty;
                            $stock->save();
                            $delta = $qty - $old;
                            StockHistory::create([
                                'warehouse_id' => $warehouse->id,
                                'item_id' => $item->id,
                                'quantity_before' => $old,
                                'quantity_after' => $qty,
                                'quantity_change' => $delta,
                                'reference_type' => 'import',
                                'reference_id' => 0,
                                'description' => 'Absolute sync from amtradingstock.xlsx',
                                'user_id' => auth()->id() ?? 0,
                            ]);
                            $stockAdjusted++;
                        }
                    }
                } catch (\Throwable $rowEx) {
                    $errors[] = 'Row '.$row.': '.$rowEx->getMessage();
                }
            }

            $msg = "Imported items: created {$created}, updated {$updated}. Stock rows adjusted: {$stockAdjusted}.";
            if (!empty($errors)) {
                return redirect()->route('admin.items.import')->with('info', $msg)->withErrors(['file' => implode("\n", array_slice($errors, 0, 10))]);
            }
            return redirect()->route('admin.items.import')->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Import apply failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['file' => 'Failed to import: ' . $e->getMessage()]);
        }
    }

    /**
     * Get all items from the Excel sheet and format them as JSON objects
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $highestRow
     * @param int $highestColumnIndex
     * @param array $headers
     * @return array
     */
    private function getAllItemsAsJson($sheet, $highestRow, $highestColumnIndex, $headers)
    {
        $items = [];


        
        // Special handling for this specific Excel file structure where most headers are empty
        Log::warning("Excel file has unusual header structure with many empty headers", ['headers' => $headers]);
        
        // Find branch columns and U.COST column by name since those headers should exist
        $branchColumns = [];
        $costIndex = null;
        
        foreach ($headers as $index => $header) {
            $headerLower = strtolower(trim($header));
            
            // Find branch columns
            if (in_array($headerLower, ['bicha', 'kemer', 'furi'])) {
                $branchColumns[$headerLower] = $index;
                Log::info("Found branch column: {$header} at index {$index}");
            }
            
            // Find U.COST column
            if ($headerLower === 'u.cost' || $headerLower === 'ucost' || $headerLower === 'cost') {
                $costIndex = $index;
                Log::info("Found cost column: {$header} at index {$index}");
            }
        }
        
        // Process all data rows from row 3 to the end (since row 2 is now the header row)
        for ($row = 3; $row <= $highestRow; $row++) {
            // Read the row data
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $rowData[] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
            }
            
            // Skip empty rows
            if (count(array_filter($rowData, fn($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            
            // Debug row data
            Log::debug("Processing row data at row {$row}:", $rowData);
            
            // Since most headers are empty, we'll assume a fixed column structure based on observation
            // This is custom handling for this specific Excel format
            // We'll use the row data to determine item properties
            
            // Find columns by their exact header names
            $nameIndex = null;
            $codeIndex = null;
            
            // Look for exact header matches
            foreach ($headers as $index => $header) {
                $headerUpper = strtoupper(trim($header));
                
                if ($headerUpper === 'ITEM') {
                    $nameIndex = $index;
                    Log::info("Found ITEM (name) column at index {$index}");
                }
                
                if ($headerUpper === 'CODE') {
                    $codeIndex = $index;
                    Log::info("Found CODE (sku) column at index {$index}");
                }
            }
            
            // Extract values from the identified columns
            $name = '';
            if ($nameIndex !== null && isset($rowData[$nameIndex]) && !empty(trim($rowData[$nameIndex]))) {
                $name = trim($rowData[$nameIndex]);
                Log::debug("Found name: {$name} from ITEM column at index {$nameIndex}");
            }
            
            $code = '';
            if ($codeIndex !== null && isset($rowData[$codeIndex]) && !empty(trim($rowData[$codeIndex]))) {
                $code = trim($rowData[$codeIndex]);
                Log::debug("Found code: {$code} from CODE column at index {$codeIndex}");
            }
            
            // Process branch quantities using the branch columns we identified earlier
            $branchData = [];
            foreach ($branchColumns as $branch => $idx) {
                // Get quantity from the specified column
                $qty = isset($rowData[$idx]) && is_numeric($rowData[$idx]) ? floatval($rowData[$idx]) : 0;
                $branchData[$branch] = $qty;
                Log::debug("Branch {$branch} quantity: {$qty} at index {$idx} for row {$row}");
            }
            
            // Set default values for other item properties
            $um = 1;  // Default unit quantity is 1
            
            // Find U.M and U.COST columns by their exact header names
            $umIndex = null;
            $costIndex = null;
            
            // Look for exact header matches
            foreach ($headers as $index => $header) {
                $headerUpper = strtoupper(trim($header));
                
                if ($headerUpper === 'U.M' || $headerUpper === 'UM') {
                    $umIndex = $index;
                    Log::info("Found U.M (unit_quantity) column at index {$index}");
                }
                
                if ($headerUpper === 'U.COST' || $headerUpper === 'UCOST' || $headerUpper === 'COST') {
                    $costIndex = $index;
                    Log::info("Found U.COST (cost per item) column at index {$index}");
                }
            }
            
            // Extract U.M value (unit_quantity) if found
            $um = 1;  // Default unit quantity is 1
            if ($umIndex !== null && isset($rowData[$umIndex])) {
                $umValue = trim($rowData[$umIndex]);
                if (is_numeric($umValue) && floatval($umValue) > 0) {
                    $um = floatval($umValue);
                    Log::info("Found U.M value: {$um} at index {$umIndex} for row {$row}");
                }
            }
            
            // Extract cost from U.COST column (cost per item) if found
            $cost = 0;    // Default cost
            $price = 0;   // Default price (will be set to same as cost)
            
            if ($costIndex !== null && isset($rowData[$costIndex])) {
                $costValue = trim($rowData[$costIndex]);
                if (is_numeric($costValue)) {
                    $cost = floatval($costValue);
                    $price = $cost;  // Use the same value for both cost and price
                    Log::info("Found U.COST value: {$cost} at index {$costIndex} for row {$row}");
                }
            }
            
            // Fallback for item name if empty - use row number
            if (empty($name)) {
                $name = "Item #{$row}";
                Log::debug("Using fallback name: {$name}");
            }
            
            // Fallback for code if empty - use name
            if (empty($code) && !empty($name)) {
                $code = str_replace(' ', '-', strtoupper(substr($name, 0, 10))) . '-' . $row;
                Log::debug("Using fallback code: {$code}");
            }
            
            // Always ensure price equals cost - force this relationship
            $price = $cost;

        // U.M is number of items per piece (unit_quantity)
        // Cost is the price of one ITEM (not piece)
        // Price is the price of one ITEM (not piece)
        
        // The cost_price_per_unit should be the cost of a single item (which is already in $cost)
        $cost_price_per_unit = $cost;
        $selling_price_per_unit = $price;
        
        // The cost_price should be cost * unit_quantity (cost of entire piece)
        $total_cost = $um && is_numeric($um) && floatval($um) > 0 ? $cost * floatval($um) : $cost;
        $total_price = $um && is_numeric($um) && floatval($um) > 0 ? $price * floatval($um) : $price;
        
        // log the values
        Log::info("UM (unit_quantity): {$um}");
        Log::info("Cost per item: {$cost}");
        Log::info("Price per item: {$price}");
        Log::info("Cost per unit (per item): {$cost_price_per_unit}");
        Log::info("Selling price per unit (per item): {$selling_price_per_unit}");
        Log::info("Total cost (per piece): {$total_cost}");
        Log::info("Total price (per piece): {$total_price}");
            
            // Create a complete item object with all fields from the Item model
            $item = [
                // Required fields
                'name' => $name,
                'sku' => $code,  // Use code as SKU
                'barcode' => $code, // Use code as barcode too for now
                'category_id' => 1,  // Default category ID, will be updated from form
                
                // Pricing fields
                'cost_price' => $total_cost,  // Total cost per piece (cost * unit_quantity)
                'selling_price' => $total_price,  // Total price per piece (price * unit_quantity)
                'cost_price_per_unit' => $cost_price_per_unit,  // Cost per item
                'selling_price_per_unit' => $selling_price_per_unit,  // Price per item
                
                // Inventory fields
                'reorder_level' => 10, // Default reorder level
                'unit' => 'pcs',  // Unit of measurement (always pcs)
                'unit_quantity' => $um && is_numeric($um) && floatval($um) > 0 ? floatval($um) : 1,  // U.M maps to unit_quantity
                'item_unit' => 'piece',  // Individual item unit
                
                // Additional fields
                'brand' => '',
                'description' => '',
                'image_path' => null,
                'is_active' => true,
                
                // Audit fields will be filled automatically
                'created_by' => auth()->id() ?? 1,
                'updated_by' => null,
                'deleted_by' => null,
            ];
            
            // Store branch quantities separately
            $branchQuantities = $branchData;
            
            Log::info("Created item object for row {$row}", $item);
            
            // Add the item to our collection
            $items[] = $item;
        }
        
        return $items;
    }
    
    /**
     * Process JSON-based import data.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function processJsonImport(Request $request)
    {
        $defaultCategoryId = (int)$request->input('default_category_id', 0);
        $itemData = $request->input('item_data', []);
        
        // Check if we have any data
        if (empty($itemData)) {
            return redirect()->route('admin.items.import')->withErrors(['file' => 'No item data provided for import.']);
        }
        
        // Initialize counters
        $created = 0;
        $updated = 0;
        $stockAdjusted = 0;
        $errors = [];
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Process each item
            foreach ($itemData as $jsonItem) {
                $data = json_decode($jsonItem, true);
                
                // Skip invalid data
                if (!is_array($data) || empty($data['name'])) {
                    $errors[] = 'Invalid item data found in JSON';
                    continue;
                }
                
                // Extract branch quantities if present, but remove from main item data
                $branches = [];
                if (isset($data['branches'])) {
                    $branches = $data['branches'];
                    unset($data['branches']); // Remove branches from item data
                }
                
                // Use the item data as-is since it now contains all required fields
                // Just ensure we have the minimum required fields
                if (empty($data['name'])) {
                    $data['name'] = 'Unknown Item';
                }
                
                if (empty($data['sku'])) {
                    $data['sku'] = str_replace(' ', '-', strtoupper(substr($data['name'], 0, 10))) . '-' . rand(1000, 9999);
                }
                
                // Find or create item based on SKU or name
                $item = null;
                if (!empty($data['sku'])) {
                    $item = Item::firstOrNew(['sku' => $data['sku']]);
                } else {
                    $item = Item::firstOrNew(['name' => $data['name']]);
                }
                
                $isNew = !$item->exists;
                
                // Update item with all provided data
                foreach ($data as $field => $value) {
                    // Skip fields that don't exist in the model
                    if (in_array($field, $item->getFillable())) {
                        $item->{$field} = $value;
                    }
                }
                
                // Override category if provided in form
                if ($defaultCategoryId > 0) {
                    $item->category_id = $defaultCategoryId;
                }
                
                // Ensure is_active is set
                $item->is_active = true;
                
                // Save the item
                $item->save();
                
                // Count created/updated items
                $isNew ? $created++ : $updated++;
                
                // Process branch quantities
                if (!empty($branches)) {
                    $stockService = new StockMovementService();
                    
                    foreach ($branches as $branchName => $quantity) {
                        // Skip empty quantities
                        if (empty($quantity)) continue;
                        
                        // Find branch by name
                        $branch = Branch::whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first();
                        if (!$branch) continue;
                        
                        // Get default warehouse
                        $warehouse = $stockService->ensureBranchWarehouse($branch->id);
                        
                        // Create/update stock
                        $stock = Stock::firstOrNew(['warehouse_id' => $warehouse->id, 'item_id' => $item->id]);
                        $oldQuantity = $stock->exists ? $stock->quantity : 0;
                        $stock->quantity = $quantity;
                        $stock->save();
                        
                        // Create stock history record
                        if ($oldQuantity != $quantity) {
                            StockHistory::create([
                                'warehouse_id' => $warehouse->id,
                                'item_id' => $item->id,
                                'quantity_before' => $oldQuantity,
                                'quantity_after' => $quantity,
                                'quantity_change' => $quantity - $oldQuantity,
                                'reference_type' => 'import',
                                'reference_id' => 0,
                                'description' => 'JSON import stock adjustment',
                                'user_id' => auth()->id() ?? 0,
                            ]);
                            
                            $stockAdjusted++;
                        }
                    }
                }
            }
            
            // Commit transaction
            DB::commit();
            
            $msg = "Imported items: created {$created}, updated {$updated}. Stock rows adjusted: {$stockAdjusted}.";
            if (!empty($errors)) {
                return redirect()->route('admin.items.import')->with('info', $msg)->withErrors(['file' => implode("\n", array_slice($errors, 0, 10))]);
            }
            return redirect()->route('admin.items.import')->with('success', $msg);
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            Log::error('JSON import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('admin.items.import')->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    private function suggestMappings(array $headers): array
    {
        $map = [];
        $find = function(array $candidates) use ($headers) {
            foreach ($headers as $h) {
                $hn = strtolower(trim($h));
                foreach ($candidates as $c) {
                    if (str_contains($hn, $c)) {
                        return $h;
                    }
                }
            }
            return null;
        };

        $map['name'] = $find(['name', 'item']);
        $map['sku'] = $find(['sku', 'code']);
        $map['barcode'] = $find(['barcode', 'bar code']);
        $map['category'] = $find(['category']);
        $map['unit'] = $find(['unit']);
        $map['unit_quantity'] = $find(['unit quantity', 'unit qty', 'qty per', 'pack']);
        $map['cost_price'] = $find(['cost', 'purchase']);
        $map['selling_price'] = $find(['sell', 'price', 'retail']);
        $map['reorder_level'] = $find(['reorder', 'min']);
        $map['brand'] = $find(['brand']);
        $map['description'] = $find(['description', 'desc']);

        return $map;
    }
}
