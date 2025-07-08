<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    public Category $category;

    protected $listeners = ['category-saved' => 'redirectToIndex'];

    public function mount(Category $category)
    {
        $this->category = $category;
    }

    public function redirectToIndex()
    {
        return redirect()->route('admin.categories.index');
    }

    public function render()
    {
        return view('livewire.categories.edit')
            ->title('Edit Category - ' . $this->category->name);
    }
}
