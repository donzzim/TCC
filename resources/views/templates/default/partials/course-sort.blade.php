@once
    <style>
        .course-date-sort-control {
            width: 100%;
        }

        @media (min-width: 640px) {
            .course-date-sort-control {
                flex: 0 0 14rem;
                width: 14rem;
                max-width: 14rem;
            }
        }
    </style>
@endonce

<label class="course-date-sort-control relative block">
    <span class="sr-only">Ordenar cursos</span>
    <i data-lucide="arrow-up-down" class="absolute w-4 h-4 -translate-y-1/2 left-3 top-1/2 text-slate-400"></i>
    <select
        wire:model.live="dateSort"
        class="w-full h-11 appearance-none rounded-lg border-slate-200 bg-white py-2 pl-10 pr-9 text-sm text-slate-900 shadow-sm focus:border-blue-700 focus:ring-blue-700"
        title="Ordenar cursos"
    >
        <option value="near_today">Próximos de hoje</option>
        <option value="date_asc">Data crescente</option>
        <option value="date_desc">Data decrescente</option>
    </select>
    <i data-lucide="chevron-down" class="pointer-events-none absolute w-4 h-4 -translate-y-1/2 right-3 top-1/2 text-slate-400"></i>
</label>
