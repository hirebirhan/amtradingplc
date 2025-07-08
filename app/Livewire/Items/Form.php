<?php

namespace App\Livewire\Items;

use Livewire\Component;
use App\Models\Item;

class Form extends Component
{
    public $item;
    public $isEdit = false;

    public function mount(Item $item = null)
    {
        $this->item = $item ?? new Item();
        $this->isEdit = $item ? true : false;
    }

    public function render()
    {
        return view('livewire.items.form');
    }
}