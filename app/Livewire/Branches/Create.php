<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
#[Title('Create Branch')]
class Create extends Component
{
    public $name;
    public $address;
    public $phone;
    public $email;
    // isActive is defaulted to true and removed from UI
    private $isActive = true;

    protected $rules = [
        'name' => 'required|string|max:255|unique:branches,name',
        'address' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:255',
    ];

    private function generateBranchCode(): string
    {
        $prefix = 'BR-';
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            $attempts++;
            // Generate a 3-character suffix
            $suffix = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 3));
            $code = $prefix . $suffix;
        } while (Branch::where('code', $code)->exists() && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            throw new \Exception('Unable to generate unique branch code after multiple attempts.');
        }
        
        return $code;
    }

    public function save()
    {
        try {
            $validated = $this->validate();

            // Generate unique branch code
            $validated['code'] = $this->generateBranchCode();
            $validated['is_active'] = true; // Always set to true by default

            $branch = Branch::create($validated);

            Log::info('Branch created successfully', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'created_by' => auth()->id()
            ]);

            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => "Branch '{$branch->name}' created successfully with code '{$branch->code}'.",
                'title' => 'Success'
            ]);
            
            return redirect()->route('admin.branches.index');
            
        } catch (\Exception $e) {
            Log::error('Failed to create branch', [
                'error' => $e->getMessage(),
                'form_data' => $this->only(['name', 'address', 'phone', 'email']),
                'user_id' => auth()->id()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to create branch. Please try again.',
                'title' => 'Error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.branches.create', [
            'active' => 'branches',
        ]);
    }
}