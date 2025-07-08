@props([
    'field' => '',
    'title' => '',
    'sortField' => null,
    'sortDirection' => 'asc',
])

<th 
    wire:click="sortBy('{{ $field }}')" 
    class="cursor-pointer {{ $attributes->get('class') }}"
    {{ $attributes->except('class') }}
>
    {{ $title ?: $slot }}
    
    @if($sortField === $field)
        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1 text-primary"></i>
    @else
        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
    @endif
</th> 