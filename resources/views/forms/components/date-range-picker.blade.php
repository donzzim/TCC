<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="dateRangePicker({
            state: $wire.entangle('{{ $getStatePath() }}').live,
            start: '{{ $getState()['start'] ?? null }}',
            end: '{{ $getState()['end'] ?? null }}',
            year: {{ $year ?? 'null' }}
        })" class="p-4 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-600">
        {{-- Cabeçalho com Navegação --}}
        <div class="flex items-center justify-between mb-4">
            <button type="button" x-on:click="prevMonth()"
                class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                <x-filament::icon icon="heroicon-o-chevron-left" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </button>
            <div class="flex items-center gap-x-2">
                <select x-model.number="currentMonth" @change="generateCalendar()"
                    class="w-32 text-sm border-none shadow-sm focus:border-none focus:ring-primary-500 dark:bg-gray-800 dark:text-white p-2">
                    <template x-for="(month, index) in months" :key="index">
                        <option :value="index" x-text="month" :selected="index === currentMonth"></option>
                    </template>
                </select>
                <select x-model.number="currentYear" @change="generateCalendar()"
                    class="w-24 text-sm border-none shadow-sm focus:border-none focus:ring-primary-500 dark:bg-gray-800 dark:text-white p-2">
                    <template x-for="year in years" :key="year">
                        <option :value="year" x-text="year" :selected="year === currentYear"></option>
                    </template>
                </select>
            </div>
            <button type="button" x-on:click="nextMonth()"
                class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                <x-filament::icon icon="heroicon-o-chevron-right" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </button>
        </div>

        {{-- Grid do Calendário --}}
        <div>
            {{-- Dias da Semana --}}
            <div class="grid grid-cols-7 gap-1 text-xs text-center text-gray-500 dark:text-gray-400 mb-2">
                <template x-for="day in daysOfWeek" :key="day">
                    <div x-text="day"></div>
                </template>
            </div>
            {{-- Dias do Mês --}}
            <div class="grid grid-cols-7 gap-0"> {{-- Gap set to 0 for connected effect --}}
                <template x-for="blankDay in blankDays">
                    <div></div>
                </template>
                <template x-for="day in daysInMonth" :key="day">
                    <div x-text="day" x-on:click="selectDate(day)" :class="{
                            'cursor-pointer': day,
                            // Estilo para o início e fim do período (círculo sólido)
                            'bg-primary-600 dark:bg-primary-500 text-white font-semibold rounded-full': isStart(day) || isEnd(day),
                            
                            // ESTILO PARA OS DIAS *ENTRE* O INÍCIO E O FIM
                            'bg-primary-100 dark:bg-primary-400/20 text-primary-700 dark:text-primary-300': isInRange(day),

                            // Arredondamento para criar o efeito de pílula contínua
                            'rounded-l-full': isStart(day) && endDate,
                            'rounded-r-full': isEnd(day),
                            
                            // Estilo para os dias normais, fora do período
                            'hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full': !isStart(day) && !isEnd(day) && !isInRange(day)
                        }" class="flex items-center justify-center w-10 h-10 text-sm"></div>
                </template>
            </div>
        </div>

        {{-- Display do Período Selecionado --}}
        <div
            class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-center text-gray-600 dark:text-gray-300">
            <template x-if="startDate">
                <div>
                    <strong>Período:</strong>
                    <span x-text="formatDisplayDate(startDate) || '...'"></span>
                    <span> - </span>
                    <span x-text="formatDisplayDate(endDate) || '...'"></span>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>

{{-- O SCRIPT JAVASCRIPT PERMANECE O MESMO DA RESPOSTA ANTERIOR --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dateRangePicker', (config) => ({
            state: config.state,
            startDate: config.start,
            endDate: config.end,
            currentMonth: new Date().getMonth(),
            currentYear: config.year || new Date().getFullYear(),
            selectingStart: true,
            daysInMonth: [],
            blankDays: [],
            months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
            years: Array.from({ length: 10 }, (_, i) => (config.year || new Date().getFullYear()) - 5 + i),

            init() {
                if (this.startDate) {
                    const start = new Date(this.startDate);
                    const userTimezoneOffset = start.getTimezoneOffset() * 60000;
                    const adjustedStart = new Date(start.getTime() + userTimezoneOffset);

                    this.currentYear = adjustedStart.getFullYear();
                    this.currentMonth = adjustedStart.getMonth();
                }

                this.$watch('state', () => {
                    this.startDate = this.state?.start;
                    this.endDate = this.state?.end;
                });
                
                this.generateCalendar();
            },

            generateCalendar() {
                const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);

                this.blankDays = Array(firstDay.getDay()).fill(null);
                this.daysInMonth = Array.from({ length: lastDay.getDate() }, (_, i) => i + 1);
            },

            selectDate(day) {
                const selectedDate = new Date(this.currentYear, this.currentMonth, day);
                const formattedDate = this.formatDate(selectedDate);
                
                if (this.selectingStart || !this.startDate || selectedDate < new Date(this.startDate)) {
                    this.startDate = formattedDate;
                    this.endDate = null;
                    this.selectingStart = false;
                } else {
                    this.endDate = formattedDate;
                    this.selectingStart = true;
                }
                
                this.state = { start: this.startDate, end: this.endDate };
            },

            formatDate(date) {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            },
            
            formatDisplayDate(dateString) {
                if (!dateString) return null;
                const date = new Date(dateString);
                const userTimezoneOffset = date.getTimezoneOffset() * 60000;
                const adjustedDate = new Date(date.getTime() + userTimezoneOffset);
                return adjustedDate.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            },

            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.generateCalendar();
            },

            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.generateCalendar();
            },
            
            isStart(day) {
                if (!this.startDate) return false;
                return this.formatDate(new Date(this.currentYear, this.currentMonth, day)) === this.startDate;
            },

            isEnd(day) {
                if (!this.endDate) return false;
                return this.formatDate(new Date(this.currentYear, this.currentMonth, day)) === this.endDate;
            },

            isInRange(day) {
                if (!this.startDate || !this.endDate) return false;
                const currentDate = new Date(this.currentYear, this.currentMonth, day);
                // Usamos >= e <= para incluir o início e o fim no range para estilização de fundo
                return currentDate >= new Date(this.startDate) && currentDate <= new Date(this.endDate);
            }
        }));
    });
</script>