@php
    $statePath = $getStatePath();
    $segments = explode('.', $statePath);
    array_pop($segments);
    $agendaableStatePath = implode('.', array_merge($segments, [$getAgendaableField()]));
    $agendas = $getAgendas();
    $agendaables = $getAgendaables();
    $componentKey = 'date-range-picker-v2-' . md5($statePath . '|' . json_encode($agendas) . '|' . json_encode($agendaables));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:key="{{ $componentKey }}" x-data="window.agendaCalendarPicker({
        state: $wire.$entangle('{{ $statePath }}'),
        agendaable: $wire.$entangle('{{ $agendaableStatePath }}'),
        agendas: @js($agendas),
        agendaables: @js($agendaables),
    })"
        class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="prevMonth()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Mes anterior">
                    <x-filament::icon icon="heroicon-o-chevron-left" class="h-5 w-5" />
                </button>

                <select x-bind:value="currentMonth" x-on:change="currentMonth = Number($event.target.value); generateCalendar()"
                    class="min-w-36 rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <template x-for="(month, index) in months" :key="index">
                        <option :value="index" :selected="currentMonth === index" x-text="month"></option>
                    </template>
                </select>

                <select x-bind:value="currentYear" x-on:change="currentYear = Number($event.target.value); generateCalendar()"
                    class="w-28 rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <template x-for="year in years" :key="year">
                        <option :value="year" :selected="currentYear === year" x-text="year"></option>
                    </template>
                </select>

                <button type="button" x-on:click="nextMonth()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Proximo mes">
                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-5 w-5" />
                </button>
            </div>

            <div
                class="inline-flex rounded-md border border-gray-200 bg-gray-50 p-1 text-sm dark:border-gray-700 dark:bg-gray-800">
                <button type="button" x-on:click="setMode('intervalo')"
                    :class="mode === 'intervalo' ?
                        'bg-white text-primary-700 shadow-sm dark:bg-gray-900 dark:text-primary-300' :
                        'text-gray-600 dark:text-gray-300'"
                    class="rounded px-3 py-1.5">
                    Intervalo
                </button>
                <button type="button" x-on:click="setMode('selecao')"
                    :class="mode === 'selecao' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-900 dark:text-primary-300' :
                        'text-gray-600 dark:text-gray-300'"
                    class="rounded px-3 py-1.5">
                    Selecao
                </button>
            </div>
        </div>

        <div class="grid gap-4">
            <div class="space-y-2">
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                    <template x-for="day in daysOfWeek" :key="day">
                        <div class="py-1" x-text="day"></div>
                    </template>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <template x-for="blankDay in blankDays" :key="'blank-' + blankDay">
                        <div class="aspect-square"></div>
                    </template>

                    <template x-for="day in daysInMonth" :key="day.date">
                        <button type="button" x-on:click="toggleDay(day.date)" :title="dayTitle(day.date)"
                            :class="dayClasses(day.date)"
                            class="relative flex aspect-square min-h-12 flex-col items-center justify-center rounded-md border text-sm transition">
                            <span class="font-medium" x-text="day.day"></span>
                            <div class="mt-1 flex min-h-3 gap-0.5">
                                <span x-show="hasExisting(day.date)" class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                <span x-show="isCreate(day.date)"
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                <span x-show="isDelete(day.date)" class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                <span x-show="isComplex(day.date)" class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <div class="space-y-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-3">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Inicio</span>
                        <input type="time" x-model="horaInicio" step="60" placeholder="--:--"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Termino</span>
                        <input type="time" x-model="horaTermino" step="60" placeholder="--:--"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" x-on:click="applyTime()"
                        class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500">
                        <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                        Aplicar
                    </button>

                    <button type="button" x-on:click="markSelectedForDelete()"
                        class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                        Desmarcar
                    </button>

                    <button type="button" x-on:click="clearSelection()"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
                        Limpar
                    </button>
                </div>

                <div class="flex flex-wrap gap-2 text-xs">
                    <span
                        class="rounded bg-sky-100 px-2 py-1 text-sky-700 dark:bg-sky-900 dark:text-sky-200">marcado</span>
                    <span
                        class="rounded bg-emerald-100 px-2 py-1 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">marcar</span>
                    <span
                        class="rounded bg-rose-100 px-2 py-1 text-rose-700 dark:bg-rose-900 dark:text-rose-200">desmarcar</span>
                    <span
                        class="rounded bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-900 dark:text-amber-200">multiplo</span>
                </div>

                <template x-if="selectedDates.length">
                    <div class="rounded-md bg-gray-50 p-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <span x-text="selectedDates.length"></span>
                        <span>dia(s) selecionado(s)</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>

@assets
    <script>
        window.agendaCalendarPicker ??= (config) => ({
            state: config.state,
            agendaable: config.agendaable,
            agendas: config.agendas || [],
            agendaables: config.agendaables || [],
            mode: 'intervalo',
            intervalStart: null,
            selectedDates: [],
            staged: {},
            horaInicio: '',
            horaTermino: '',
            currentMonth: new Date().getMonth(),
            currentYear: new Date().getFullYear(),
            daysInMonth: [],
            blankDays: [],
            months: ['Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro',
                'Outubro', 'Novembro', 'Dezembro'
            ],
            daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
            years: Array.from({
                length: 11
            }, (_, i) => new Date().getFullYear() - 5 + i),

            init() {
                this.agendaable = this.agendaable || this.defaultAgendaableId();
                this.staged = {};
                this.hydrateState();
                this.openFirstAgendaMonth();
                this.generateCalendar();

                this.$watch('agendaable', () => {
                    this.clearSelection();
                    this.staged = {};
                    this.openFirstAgendaMonth();
                    this.generateCalendar();
                });
            },

            defaultAgendaableId() {
                return this.agendaables[0]?.id || this.agendas[0]?.agendaable_id || null;
            },

            openFirstAgendaMonth() {
                const agenda = this.visibleAgendas()[0];

                if (!agenda?.data) {
                    return;
                }

                const [year, month] = agenda.data.split('-').map(Number);
                this.setCurrentPeriod(year, month - 1);
            },

            setCurrentPeriod(year, month) {
                this.currentYear = Number(year);
                this.currentMonth = Number(month);
                this.ensureYearVisible(this.currentYear);
            },

            ensureYearVisible(year = this.currentYear) {
                const selectedYear = Number(year);

                if (this.years.includes(selectedYear)) {
                    return;
                }

                this.years = Array.from({
                    length: 11
                }, (_, i) => selectedYear - 5 + i);
            },

            hydrateState() {
                if (!this.state?.dias) {
                    return;
                }

                this.state.dias.forEach((dia) => {
                    if (!dia?.data) {
                        return;
                    }

                    this.staged[dia.data] = {
                        ...dia
                    };
                });
            },

            visibleAgendas() {
                return this.agendas.filter((agenda) => String(agenda.agendaable_id) === String(this.agendaable));
            },

            selectedAgendaable() {
                return this.agendaables.find((item) => String(item.id) === String(this.agendaable)) || {};
            },

            agendasForDate(date) {
                return this.visibleAgendas().filter((agenda) => agenda.data === date);
            },

            simpleAgenda(date) {
                const agendas = this.agendasForDate(date);
                return agendas.length === 1 ? agendas[0] : null;
            },

            hasExisting(date) {
                return this.agendasForDate(date).length > 0 && !this.isDelete(date);
            },

            isComplex(date) {
                return this.agendasForDate(date).length > 1;
            },

            isSelected(date) {
                return this.selectedDates.includes(date);
            },

            isCreate(date) {
                return this.staged[date]?.operacao === 'create';
            },

            isUpdate(date) {
                return this.staged[date]?.operacao === 'update';
            },

            isDelete(date) {
                return this.staged[date]?.operacao === 'delete';
            },

            setMode(mode) {
                this.mode = mode;
                this.intervalStart = null;
                this.clearSelection();
            },

            generateCalendar() {
                this.currentYear = Number(this.currentYear);
                this.currentMonth = Number(this.currentMonth);
                this.ensureYearVisible(this.currentYear);

                const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);

                this.blankDays = Array.from({
                    length: firstDay.getDay()
                }, (_, i) => i);
                this.daysInMonth = Array.from({
                    length: lastDay.getDate()
                }, (_, i) => {
                    const date = this.formatDate(new Date(this.currentYear, this.currentMonth, i + 1));

                    return {
                        day: i + 1,
                        date,
                    };
                });
            },

            toggleDay(date) {
                if (this.isComplex(date)) {
                    return;
                }

                if (this.mode === 'intervalo') {
                    this.toggleInterval(date);
                } else {
                    this.toggleSingle(date);
                }

                this.refreshTimeInputs();
            },

            toggleInterval(date) {
                if (!this.intervalStart) {
                    this.intervalStart = date;
                    this.selectedDates = [date];
                    return;
                }

                this.selectedDates = this.rangeDates(this.intervalStart, date)
                    .filter((item) => !this.isComplex(item));
                this.intervalStart = null;
            },

            toggleSingle(date) {
                if (this.isSelected(date)) {
                    this.selectedDates = this.selectedDates.filter((item) => item !== date);
                    return;
                }

                this.selectedDates = [...this.selectedDates, date].sort();
            },

            applyTime() {
                if (!this.selectedDates.length || !this.horaInicio || !this.horaTermino) {
                    return;
                }

                this.selectedDates.forEach((date) => {
                    if (this.isComplex(date)) {
                        return;
                    }

                    const existing = this.simpleAgenda(date);
                    const base = existing || {};
                    const agendaable = this.selectedAgendaable();

                    this.staged[date] = {
                        id: base.id || null,
                        data: date,
                        inicio: `${date} ${this.horaInicio}:00`,
                        termino: `${date} ${this.horaTermino}:00`,
                        titulo: base.titulo || 'Curso',
                        descricao: base.descricao || agendaable.titulo || '',
                        local_id: base.local_id || agendaable.local_id || null,
                        atualizado_por: base.atualizado_por || null,
                        operacao: base.id ? 'update' : 'create',
                    };
                });

                this.clearSelection(false);
                this.syncState();
            },

            markSelectedForDelete() {
                this.selectedDates.forEach((date) => {
                    if (this.isComplex(date)) {
                        return;
                    }

                    const existing = this.simpleAgenda(date);

                    if (existing?.bloqueado) {
                        return;
                    }

                    if (existing?.id) {
                        this.staged[date] = {
                            ...existing,
                            operacao: 'delete',
                        };
                    } else {
                        delete this.staged[date];
                    }
                });

                this.clearSelection(false);
                this.syncState();
            },

            refreshTimeInputs() {
                if (!this.selectedDates.length) {
                    this.horaInicio = '';
                    this.horaTermino = '';
                    return;
                }

                const horarios = this.selectedDates
                    .filter((date) => !this.isComplex(date))
                    .map((date) => this.staged[date] || this.simpleAgenda(date))
                    .filter(Boolean);

                const inicios = [...new Set(horarios.map((item) => this.timeFromDateTime(item.inicio)))];
                const terminos = [...new Set(horarios.map((item) => this.timeFromDateTime(item.termino)))];

                this.horaInicio = inicios.length === 1 ? inicios[0] : '';
                this.horaTermino = terminos.length === 1 ? terminos[0] : '';
            },

            clearSelection(resetInputs = true) {
                this.selectedDates = [];
                this.intervalStart = null;

                if (resetInputs) {
                    this.horaInicio = '';
                    this.horaTermino = '';
                }
            },

            syncState() {
                this.state = {
                    agendaable_id: this.agendaable,
                    modo_persistencia: 'operacoes',
                    dias: Object.values(this.staged)
                        .filter((dia) => dia.operacao !== 'keep')
                        .sort((a, b) => a.data.localeCompare(b.data)),
                };
            },

            dayTitle(date) {
                if (this.isComplex(date)) {
                    return 'Multiplo';
                }

                if (this.isDelete(date)) {
                    return 'Desmarcar';
                }

                if (this.isCreate(date)) {
                    return 'Marcar';
                }

                if (this.isUpdate(date)) {
                    return 'Atualizar';
                }

                return date;
            },

            dayClasses(date) {
                const classes = [];

                if (this.isSelected(date)) {
                    classes.push('ring-2 ring-primary-500 ring-offset-1 dark:ring-offset-gray-900');
                }

                if (this.isComplex(date)) {
                    classes.push(
                        'cursor-not-allowed border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100'
                        );
                    return classes.join(' ');
                }

                if (this.isDelete(date)) {
                    classes.push(
                        'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-100'
                        );
                    return classes.join(' ');
                }

                if (this.isCreate(date)) {
                    classes.push(
                        'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100'
                        );
                    return classes.join(' ');
                }

                if (this.isSelected(date) && !this.simpleAgenda(date)) {
                    classes.push(
                        'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100'
                        );
                    return classes.join(' ');
                }

                if (this.isUpdate(date) || this.hasExisting(date)) {
                    classes.push(
                        'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100'
                        );
                    return classes.join(' ');
                }

                classes.push(
                    'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'
                    );
                return classes.join(' ');
            },

            rangeDates(start, end) {
                const dates = [];
                const current = this.parseDate(start);
                const last = this.parseDate(end);

                if (current > last) {
                    return this.rangeDates(end, start);
                }

                while (current <= last) {
                    dates.push(this.formatDate(current));
                    current.setDate(current.getDate() + 1);
                }

                return dates;
            },

            parseDate(value) {
                const [year, month, day] = value.split('-').map(Number);
                return new Date(year, month - 1, day);
            },

            formatDate(date) {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date
                    .getDate()).padStart(2, '0');
            },

            timeFromDateTime(value) {
                if (!value) {
                    return '';
                }

                return String(value).slice(11, 16);
            },

            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                    this.ensureYearVisible(this.currentYear);
                } else {
                    this.currentMonth--;
                }

                this.generateCalendar();
            },

            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                    this.ensureYearVisible(this.currentYear);
                } else {
                    this.currentMonth++;
                }

                this.generateCalendar();
            },
        });
    </script>
@endassets
