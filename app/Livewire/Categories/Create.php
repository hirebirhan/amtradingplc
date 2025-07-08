<?php

namespace App\Livewire\Categories;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Create extends Component
{
    protected $listeners = ['category-saved' => 'handleCategorySaved'];

    public function handleCategorySaved($categoryId)
    {
        // Redirect to categories index after successful creation
        return $this->redirect(route('admin.categories.index'));
    }

    public function render()
    {
        return view('livewire.categories.create')
            ->title('Create Category');
    }
}
