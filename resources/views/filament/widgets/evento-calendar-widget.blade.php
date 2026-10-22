<x-filament-widgets::widget>
    <x-filament::card>
        @php
            $cronogramaPresenceKeys = collect($listEventDays)
                ->flatMap(fn($dayGroup) => collect($dayGroup['events'] ?? [])->pluck('presence_key'))
                ->filter()
                ->unique()
                ->values()
                ->all();
        @endphp

        <div
            wire:key="cronograma-presence-root-{{ $viewMode }}-{{ $currentMonth->format('Y-m') }}"
            x-data="cronogramaPresence({
                keys: @js($cronogramaPresenceKeys),
                currentUser: @js($this->getUsuarioAtualPresencaCronograma()),
            })"
            x-init="init()"
            x-on:cronograma-presence-refresh.window="refreshSoon()"
            x-on:cronograma-presence-local-enter.window="localEnter($event.detail)"
        >
            @include('filament.widgets.evento-calendar-widget.header')

            @if ($viewMode === 'grid')
                @include('filament.widgets.evento-calendar-widget.calendar')
            @endif

            @if ($viewMode === 'list')
                @include('filament.widgets.evento-calendar-widget.list')
            @endif

            @if ($viewMode === 'tasks')
                @include('filament.widgets.evento-calendar-widget.tasks')
            @endif
        </div>
    </x-filament::card>

    @include('filament.widgets.evento-calendar-widget.presence-script')

    <x-filament-actions::modals />
</x-filament-widgets::widget>
