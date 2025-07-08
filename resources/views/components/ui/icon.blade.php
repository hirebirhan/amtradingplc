@props([
    'name' => '',
    'size' => 'md', // xs, sm, md, lg, xl, custom numeric value
    'class' => '',
    'type' => 'fas', // fas, far, fal, fad, fab
    'color' => '', // Auto-inherit from parent or specify: primary, success, etc.
])

@php
    // Basic validation
    if (empty($name)) {
        return;
    }
    
    // Font Awesome icon type prefix
    $iconType = $type ?: 'fas';
    
    // Size classes
    $sizeClasses = [
        'xs' => 'fs-6',
        'sm' => 'fs-5',
        'md' => 'fs-4',
        'lg' => 'fs-3',
        'xl' => 'fs-2',
    ];
    
    // Color classes
    $colorClass = '';
    if ($color) {
        // Check if it's a semantic color name
        $semanticColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
        if (in_array($color, $semanticColors)) {
            $colorClass = "text-$color";
        } else {
            // Assume it's a direct color value
            $inlineStyle = "color: $color;";
        }
    }
    
    // Final classes
    $classes = $iconType;
    $classes .= ' fa-' . $name;
    
    // Handle numeric size (direct font-size)
    if (is_numeric($size)) {
        $inlineStyle = "font-size: {$size}px;";
    } else {
        $classes .= ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
    }
    
    $classes .= ' ' . $colorClass;
    $classes .= ' ' . $class;
@endphp

<i class="{{ $classes }}" style="{{ $inlineStyle ?? '' }}" {{ $attributes }}></i> 