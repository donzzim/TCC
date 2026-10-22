@props([
    'default' => 1,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    'twoXl' => null,
])

@php
    $columnClasses = [
        '1' => 'grid-cols-1',
        '2' => 'grid-cols-2',
        '3' => 'grid-cols-3',
        '4' => 'grid-cols-4',
        '5' => 'grid-cols-5',
        '6' => 'grid-cols-6',
        '12' => 'grid-cols-12',
    ];

    $breakpointClasses = [
        'sm' => [
            '1' => 'sm:grid-cols-1',
            '2' => 'sm:grid-cols-2',
            '3' => 'sm:grid-cols-3',
            '4' => 'sm:grid-cols-4',
            '5' => 'sm:grid-cols-5',
            '6' => 'sm:grid-cols-6',
            '12' => 'sm:grid-cols-12',
        ],
        'md' => [
            '1' => 'md:grid-cols-1',
            '2' => 'md:grid-cols-2',
            '3' => 'md:grid-cols-3',
            '4' => 'md:grid-cols-4',
            '5' => 'md:grid-cols-5',
            '6' => 'md:grid-cols-6',
            '12' => 'md:grid-cols-12',
        ],
        'lg' => [
            '1' => 'lg:grid-cols-1',
            '2' => 'lg:grid-cols-2',
            '3' => 'lg:grid-cols-3',
            '4' => 'lg:grid-cols-4',
            '5' => 'lg:grid-cols-5',
            '6' => 'lg:grid-cols-6',
            '12' => 'lg:grid-cols-12',
        ],
        'xl' => [
            '1' => 'xl:grid-cols-1',
            '2' => 'xl:grid-cols-2',
            '3' => 'xl:grid-cols-3',
            '4' => 'xl:grid-cols-4',
            '5' => 'xl:grid-cols-5',
            '6' => 'xl:grid-cols-6',
            '12' => 'xl:grid-cols-12',
        ],
        '2xl' => [
            '1' => '2xl:grid-cols-1',
            '2' => '2xl:grid-cols-2',
            '3' => '2xl:grid-cols-3',
            '4' => '2xl:grid-cols-4',
            '5' => '2xl:grid-cols-5',
            '6' => '2xl:grid-cols-6',
            '12' => '2xl:grid-cols-12',
        ],
    ];

    $classes = ['grid', $columnClasses[(string) $default] ?? 'grid-cols-1'];

    foreach (['sm' => $sm, 'md' => $md, 'lg' => $lg, 'xl' => $xl, '2xl' => $twoXl] as $breakpoint => $columns) {
        if (filled($columns) && isset($breakpointClasses[$breakpoint][(string) $columns])) {
            $classes[] = $breakpointClasses[$breakpoint][(string) $columns];
        }
    }
@endphp

<div {{ $attributes->class($classes) }}>
    {{ $slot }}
</div>
