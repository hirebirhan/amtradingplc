<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Enums\Department;
use App\Models\Position;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class DepartmentsPositions extends Component
{
    use WithPagination;

    public $selectedDepartment = null;
    public $newDepartmentName = '';
    public $isAddingPosition = false;
    public $newPositionName = '';
    public $newPositionDepartment = '';
    public $editingPosition = null;
    public $editPositionName = '';
    public $editPositionDepartment = '';
    public $search = '';
    public $perPage = 10;
    
    public function render()
    {
        $departments = Department::toArray();
        
        $positions = Position::query()
            ->when($this->selectedDepartment, function ($query, $department) {
                return $query->where('department', $department);
            })
            ->when($this->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($this->perPage);
        
        return view('livewire.admin.settings.departments-positions', [
            'departments' => $departments,
            'positions' => $positions,
        ])->title('Departments & Positions');
    }
    
    public function selectDepartment($department)
    {
        $this->selectedDepartment = $department;
        $this->resetPage();
    }
    
    public function toggleAddPosition()
    {
        $this->isAddingPosition = !$this->isAddingPosition;
        $this->newPositionName = '';
        $this->newPositionDepartment = $this->selectedDepartment ?? '';
    }
    
    public function addPosition()
    {
        $this->validate([
            'newPositionName' => 'required|string|min:2|max:100',
            'newPositionDepartment' => 'required|string'
        ]);
        
        Position::create([
            'name' => $this->newPositionName,
            'department' => $this->newPositionDepartment,
            'is_active' => true
        ]);
        
        session()->flash('success', 'Position added successfully.');
        $this->newPositionName = '';
        $this->isAddingPosition = false;
    }
    
    public function editPosition($id)
    {
        $position = Position::findOrFail($id);
        $this->editingPosition = $id;
        $this->editPositionName = $position->name;
        $this->editPositionDepartment = $position->department;
    }
    
    public function updatePosition()
    {
        $this->validate([
            'editPositionName' => 'required|string|min:2|max:100',
            'editPositionDepartment' => 'required|string'
        ]);
        
        $position = Position::findOrFail($this->editingPosition);
        $position->update([
            'name' => $this->editPositionName,
            'department' => $this->editPositionDepartment
        ]);
        
        session()->flash('success', 'Position updated successfully.');
        $this->editingPosition = null;
    }
    
    public function togglePositionStatus($id)
    {
        $position = Position::findOrFail($id);
        $position->update([
            'is_active' => !$position->is_active
        ]);
        
        session()->flash('success', 'Position status updated successfully.');
    }
    
    public function deletePosition($id)
    {
        Position::destroy($id);
        session()->flash('success', 'Position deleted successfully.');
    }
} 