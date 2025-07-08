<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Category $category;

    public function mount($categoryId)
    {
        $this->category = Category::findOrFail($categoryId);
    }

    public function render()
    {
        return view('livewire.categories.show', [
            'parent' => $this->category->parent,
            'children' => $this->category->children()->withCount('items')->get(),
            'items' => $this->category->items()->latest()->take(10)->get(),
            'itemsCount' => $this->category->items()->count(),
        ])->title('Category Details - ' . $this->category->name);
    }
}
