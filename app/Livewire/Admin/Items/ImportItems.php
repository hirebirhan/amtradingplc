<?php

namespace App\Livewire\Admin\Items;

use App\Models\Category;
use App\Services\ItemImportService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportItems extends Component
{
    use WithFileUploads;

    public $file;
    public $default_category_id;
    public $preview;
    public $categories;

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get(['id', 'name']);
    }

    public function previewUpload()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $importService = new ItemImportService();
            $this->preview = $importService->getPreviewData($this->file->getRealPath());
        } catch (\Exception $e) {
            $this->addError('file', 'Failed to read the spreadsheet: ' . $e->getMessage());
            Log::error('Import preview failed', ['error' => $e->getMessage()]);
        }
    }

    public function applyImport()
    {
        if (empty($this->preview['allItems'])) {
            session()->flash('error', 'No items to import.');
            return;
        }

        try {
            $importService = new ItemImportService();
            $result = $importService->applyJsonImport($this->preview['allItems'], $this->default_category_id);

            $message = "Imported items: created {$result['created']}, updated {$result['updated']}. Stock rows adjusted: {$result['stockAdjusted']}.";
            
            if (!empty($result['errors'])) {
                 session()->flash('info', $message);
                 $this->addError('file', implode("\n", array_slice($result['errors'], 0, 10)));
            } else {
                session()->flash('success', $message);
            }
            
            $this->preview = null; // Clear preview after import
            $this->file = null; // Clear file input

        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
            Log::error('JSON import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function render()
    {
        return view('livewire.admin.items.import-items')->layout('layouts.app');
    }
}
