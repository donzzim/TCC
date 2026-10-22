@props([
    'actions' => [],
    'fullWidth' => false,
])

<x-filament::actions
    :actions="$actions"
    :full-width="$fullWidth"
/>
