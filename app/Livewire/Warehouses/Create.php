<?php

namespace App\Livewire\Warehouses;

use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
#[Title('Create Warehouse')]
class Create extends Component
{
    public $name = '';
    public $address = '';
    public $manager_name = '';
    public $phone = '';
    public $branch_ids = [];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
            'branch_ids' => 'array',
            'branch_ids.*' => 'exists:branches,id',
        ];
    }

    private function generateWarehouseCode(): string
    {
        $prefix = 'WH-';
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            $attempts++;
            // Generate a 3-character suffix
            $suffix = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 3));
            $code = $prefix . $suffix;
        } while (Warehouse::where('code', $code)->exists() && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            throw new \Exception('Unable to generate unique warehouse code after multiple attempts.');
        }
        
        return $code;
    }

    public function save()
    {
        try {
            $validated = $this->validate();
            
            // Remove branch_ids from validated data
            $branch_ids = $validated['branch_ids'] ?? [];
            unset($validated['branch_ids']);

            // Generate unique warehouse code
            $validated['code'] = $this->generateWarehouseCode();

            // Create the warehouse
            $warehouse = Warehouse::create($validated);
            
            // Attach branches
            if (!empty($branch_ids)) {
                $warehouse->branches()->attach($branch_ids);
            }

            Log::info('Warehouse created successfully', [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'warehouse_code' => $warehouse->code,
                'created_by' => auth()->id()
            ]);

            // Use Livewire v3 notification dispatch
            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => "Warehouse '{$warehouse->name}' created successfully with code '{$warehouse->code}'.",
                'title' => 'Success'
            ]);
            
            return redirect()->route('admin.warehouses.index');
            
        } catch (\Exception $e) {
            Log::error('Failed to create warehouse', [
                'error' => $e->getMessage(),
                'form_data' => $this->only(['name', 'address', 'manager_name', 'phone']),
                'user_id' => auth()->id()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to create warehouse. Please try again.',
                'title' => 'Error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.warehouses.create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'active' => 'warehouses',
        ])->title('Create Warehouse');
    }
}