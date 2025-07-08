@props([
    'variant' => 'primary', // primary, success, info, warning, danger, secondary
    'rounded' => true,
    'size' => 'md', // sm, md, lg
    'outlined' => false
])

@php
    $baseClasses = 'badge';
    
    $variants = [
        'primary' => 'bg-primary text-white',
        'secondary' => 'bg-secondary text-white',
        'success' => 'bg-success text-white',
        'info' => 'bg-info text-white',
        'warning' => 'bg-warning text-dark',
        'danger' => 'bg-danger text-white',
        'light' => 'bg-light text-dark',
        'dark' => 'bg-dark text-white',
    ];
    
    $outlinedVariants = [
        'primary' => 'bg-primary-50 text-primary-700',
        'secondary' => 'bg-secondary bg-opacity-10 text-secondary',
        'success' => 'bg-success bg-opacity-10 text-success',
        'info' => 'bg-info bg-opacity-10 text-info',
        'warning' => 'bg-warning bg-opacity-10 text-warning',
        'danger' => 'bg-danger bg-opacity-10 text-danger',
        'light' => 'bg-light text-dark',
        'dark' => 'bg-dark bg-opacity-10 text-dark',
    ];
    
    $sizes = [
        'sm' => 'px-2 py-1 fs-8',
        'md' => 'px-2 py-1',
        'lg' => 'px-3 py-2'
    ];
    
    $classes = $baseClasses . ' ' . 
               ($outlined ? $outlinedVariants[$variant] : $variants[$variant]) . ' ' . 
               ($rounded ? 'rounded-pill' : '') . ' ' . 
               $sizes[$size];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span> 