@props([
    'default' => null,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    'twoXl' => null,
])

@php
    $spanClasses = [
        '1' => 'col-span-1',
        '2' => 'col-span-2',
        '3' => 'col-span-3',
        '4' => 'col-span-4',
        '5' => 'col-span-5',
        '6' => 'col-span-6',
        '12' => 'col-span-12',
    ];

    $breakpointClasses = [
        'sm' => [
            '1' => 'sm:col-span-1',
            '2' => 'sm:col-span-2',
            '3' => 'sm:col-span-3',
            '4' => 'sm:col-span-4',
            '5' => 'sm:col-span-5',
            '6' => 'sm:col-span-6',
            '12' => 'sm:col-span-12',
        ],
        'md' => [
            '1' => 'md:col-span-1',
            '2' => 'md:col-span-2',
            '3' => 'md:col-span-3',
            '4' => 'md:col-span-4',
            '5' => 'md:col-span-5',
            '6' => 'md:col-span-6',
            '12' => 'md:col-span-12',
        ],
        'lg' => [
            '1' => 'lg:col-span-1',
            '2' => 'lg:col-span-2',
            '3' => 'lg:col-span-3',
            '4' => 'lg:col-span-4',
            '5' => 'lg:col-span-5',
            '6' => 'lg:col-span-6',
            '12' => 'lg:col-span-12',
        ],
        'xl' => [
            '1' => 'xl:col-span-1',
            '2' => 'xl:col-span-2',
            '3' => 'xl:col-span-3',
            '4' => 'xl:col-span-4',
            '5' => 'xl:col-span-5',
            '6' => 'xl:col-span-6',
            '12' => 'xl:col-span-12',
        ],
        '2xl' => [
            '1' => '2xl:col-span-1',
            '2' => '2xl:col-span-2',
            '3' => '2xl:col-span-3',
            '4' => '2xl:col-span-4',
            '5' => '2xl:col-span-5',
            '6' => '2xl:col-span-6',
            '12' => '2xl:col-span-12',
        ],
    ];

    $classes = [];

    if (filled($default) && isset($spanClasses[(string) $default])) {
        $classes[] = $spanClasses[(string) $default];
    }

    foreach (['sm' => $sm, 'md' => $md, 'lg' => $lg, 'xl' => $xl, '2xl' => $twoXl] as $breakpoint => $columns) {
        if (filled($columns) && isset($breakpointClasses[$breakpoint][(string) $columns])) {
            $classes[] = $breakpointClasses[$breakpoint][(string) $columns];
        }
    }
@endphp

<div {{ $attributes->class($classes) }}>
    {{ $slot }}
</div>
