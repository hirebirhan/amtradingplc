<?php

namespace Tests\Feature\Livewire\Categories;

use App\Livewire\Categories\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(Show::class)
            ->assertStatus(200);
    }
}
