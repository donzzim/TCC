@props([
    'eventId',
    'field',
    'searchKind',
    'items' => [],
    'multiple' => false,
    'allowFree' => false,
    'placeholder' => 'A definir',
    'emptyText' => 'Não informado',
    'presenceKey' => null,
    'presenceArea' => null,
])

<div
    x-data="cronogramaInlineCombobox({
        eventId: @js($eventId),
        field: @js($field),
        searchKind: @js($searchKind),
        items: @js(array_values($items)),
        multiple: @js((bool) $multiple),
        allowFree: @js((bool) $allowFree),
        placeholder: @js($placeholder),
        emptyText: @js($emptyText),
        presenceKey: @js($presenceKey),
        presenceArea: @js($presenceArea ?? $field),
    })"
    x-on:click.stop
    {{ $attributes->class('relative min-w-0') }}
>
    <button
        x-show="! editing"
        type="button"
        x-on:click.stop="open()"
        class="block min-h-6 w-full rounded-md px-1 py-0.5 text-left transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:hover:bg-gray-700"
    >
        <span x-text="displayText()" class="break-words"></span>
    </button>

    <div x-show="editing" x-cloak class="relative" x-on:click.outside="close()">
        <div
            class="flex min-h-9 w-full flex-wrap items-center gap-1 rounded-md border border-primary-300 bg-white px-2 py-1 text-sm shadow-sm focus-within:ring-2 focus-within:ring-primary-500 dark:border-primary-700 dark:bg-gray-900"
        >
            <template x-for="(item, index) in items" :key="itemKey(item, index)">
                <span class="inline-flex max-w-full items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                    <span class="truncate" x-text="item.label"></span>
                    <span x-show="multiple">,</span>
                    <button
                        type="button"
                        class="rounded px-1 text-gray-400 hover:text-danger-600"
                        x-on:click.stop="remove(index)"
                        aria-label="Remover"
                    >
                        &times;
                    </button>
                </span>
            </template>

            <input
                x-ref="input"
                x-model="query"
                type="text"
                class="min-w-28 flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-gray-100"
                :placeholder="items.length ? '' : placeholder"
                x-on:input="handleInput()"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.enter.prevent="confirm()"
                x-on:keydown.comma="if (multiple) { $event.preventDefault(); confirm(); }"
                x-on:keydown.backspace="removeLastWhenEmpty()"
                x-on:keydown.escape.prevent="close()"
            />
        </div>

        <div
            x-show="resultsOpen"
            x-transition
            class="absolute left-0 right-0 z-50 mt-1 max-h-56 overflow-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-xl dark:border-gray-700 dark:bg-gray-900"
        >
            <template x-for="(result, index) in results" :key="itemKey(result, index)">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-primary-50 dark:hover:bg-primary-950"
                    :class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300': highlightedIndex === index }"
                    x-on:mouseenter="highlightedIndex = index"
                    x-on:mousedown.prevent="select(result)"
                >
                    <span class="truncate" x-text="result.label"></span>
                    <span x-show="result.type === 'free'" class="text-xs text-gray-400">nome livre</span>
                </button>
            </template>

            <button
                x-show="allowFree && normalizedQuery() && !hasExactResult()"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-950"
                x-on:mousedown.prevent="addFree()"
            >
                <span>Adicionar</span>
                <strong class="truncate" x-text="normalizedQuery()"></strong>
            </button>

            <div
                x-show="! results.length && !(allowFree && normalizedQuery())"
                class="px-3 py-2 text-gray-500"
            >
                Nenhuma opção encontrada.
            </div>
        </div>

        <div class="mt-1 min-h-4 text-xs">
            <span x-show="saving" class="text-gray-500">Salvando...</span>
            <span x-show="saved && ! saving" x-transition class="text-green-600">Salvo</span>
            <span x-show="error" x-text="error" class="text-danger-600"></span>
        </div>
    </div>
</div>

@once
    @script
        <script>
            if (!window.cronogramaInlineCombobox) {
                window.cronogramaInlineCombobox = function (config) {
                    return {
                eventId: config.eventId,
                field: config.field,
                searchKind: config.searchKind,
                presenceKey: config.presenceKey || null,
                presenceArea: config.presenceArea || config.field || 'formulario',
                items: [],
                query: '',
                results: [],
                highlightedIndex: 0,
                resultsOpen: false,
                multiple: !!config.multiple,
                allowFree: !!config.allowFree,
                placeholder: config.placeholder || 'A definir',
                emptyText: config.emptyText || 'Não informado',
                editing: false,
                saving: false,
                saved: false,
                error: '',
                searchTimer: null,
                presenceTimer: null,

                init() {
                    this.items = this.normalizeItems(config.items || []);
                },

                displayText() {
                    const text = this.items.map((item) => item.label).filter(Boolean).join(', ');

                    return text || this.emptyText;
                },

                open() {
                    this.editing = true;
                    this.saved = false;
                    this.error = '';
                    this.query = '';
                    this.enterPresence();

                    this.$nextTick(() => {
                        this.$refs.input && this.$refs.input.focus();
                        this.search();
                    });
                },

                close() {
                    if (this.saving) {
                        return;
                    }

                    this.editing = false;
                    this.query = '';
                    this.results = [];
                    this.resultsOpen = false;
                    this.error = '';
                    this.leavePresence();
                },

                normalizedQuery() {
                    return (this.query || '').replace(/,$/, '').trim();
                },

                handleInput() {
                    if (!this.multiple && this.normalizedQuery()) {
                        this.items = [];
                    }

                    window.clearTimeout(this.searchTimer);
                    this.searchTimer = window.setTimeout(() => this.search(), 220);
                },

                async search() {
                    if (!this.editing) {
                        return;
                    }

                    try {
                        const response = await this.$wire.buscarOpcoesInlineCronograma(this.searchKind, this.normalizedQuery());
                        this.results = this.normalizeItems(response || []).filter((item) => !this.hasItem(item));
                        this.highlightedIndex = 0;
                        this.resultsOpen = this.results.length > 0 || (this.allowFree && !!this.normalizedQuery());
                    } catch (error) {
                        this.results = [];
                        this.resultsOpen = false;
                    }
                },

                move(delta) {
                    if (!this.results.length) {
                        return;
                    }

                    const next = this.highlightedIndex + delta;
                    this.highlightedIndex = (next + this.results.length) % this.results.length;
                },

                confirm() {
                    if (this.results.length) {
                        this.select(this.results[this.highlightedIndex] || this.results[0]);
                        return;
                    }

                    if (this.allowFree && this.normalizedQuery()) {
                        this.addFree();
                        return;
                    }

                    this.close();
                },

                async select(option) {
                    const item = this.normalizeItem(option);

                    if (!item.label) {
                        return;
                    }

                    if (this.multiple) {
                        if (!this.hasItem(item)) {
                            this.items.push(item);
                        }
                    } else {
                        this.items = [item];
                    }

                    this.query = '';
                    this.results = [];
                    this.resultsOpen = false;
                    const saved = await this.save();

                    if (this.multiple) {
                        this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
                    } else if (saved) {
                        this.editing = false;
                        this.leavePresence();
                    }
                },

                addFree() {
                    const label = this.normalizedQuery();

                    if (!label || !this.allowFree) {
                        return;
                    }

                    this.select({ id: null, label, type: 'free' });
                },

                remove(index) {
                    this.items.splice(index, 1);
                    this.save();
                    this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
                },

                removeLastWhenEmpty() {
                    if (!this.multiple || this.query || !this.items.length) {
                        return;
                    }

                    this.items.pop();
                    this.save();
                },

                async save() {
                    this.saving = true;
                    this.saved = false;
                    this.error = '';

                    try {
                        const payload = this.items.map((item) => ({
                            id: item.id || null,
                            label: item.label || '',
                            type: item.type || (item.id ? 'option' : 'free'),
                            comentario: item.comentario || null,
                        }));
                        const response = await this.$wire.salvarCampoInlineCronograma(this.eventId, this.field, payload);

                        if (response && response.ok === false) {
                            this.error = response.message || 'Não foi possível salvar.';
                            return false;
                        }

                        this.saved = true;
                        window.setTimeout(() => this.saved = false, 1200);
                        return true;
                    } catch (error) {
                        this.error = 'Não foi possível salvar.';
                        return false;
                    } finally {
                        this.saving = false;
                    }
                },

                enterPresence() {
                    if (!this.presenceKey) {
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('cronograma-presence-local-enter', {
                        detail: { key: this.presenceKey, area: this.presenceArea },
                    }));

                    this.$wire.entrarPresencaCronograma(this.presenceKey, this.presenceArea)
                        .then(() => window.dispatchEvent(new CustomEvent('cronograma-presence-refresh')))
                        .catch(() => null);

                    window.clearInterval(this.presenceTimer);
                    this.presenceTimer = window.setInterval(() => this.renewPresence(), 15000);
                },

                renewPresence() {
                    if (!this.presenceKey || !this.editing) {
                        return;
                    }

                    this.$wire.renovarPresencaCronograma(this.presenceKey, this.presenceArea)
                        .catch(() => null);
                },

                leavePresence() {
                    window.clearInterval(this.presenceTimer);
                    this.presenceTimer = null;

                    if (!this.presenceKey) {
                        return;
                    }

                    this.$wire.sairPresencaCronograma(this.presenceKey, this.presenceArea)
                        .then(() => window.dispatchEvent(new CustomEvent('cronograma-presence-refresh')))
                        .catch(() => null);
                },

                destroy() {
                    this.leavePresence();
                },

                normalizeItems(items) {
                    return (Array.isArray(items) ? items : [])
                        .map((item) => this.normalizeItem(item))
                        .filter((item) => item.label);
                },

                normalizeItem(item) {
                    if (typeof item === 'string') {
                        return { id: null, label: item.trim(), type: 'free' };
                    }

                    return {
                        id: item && item.id ? Number(item.id) : null,
                        label: item && (item.label || item.name || item.nome) ? String(item.label || item.name || item.nome).trim() : '',
                        type: item && item.type ? item.type : (item && item.id ? 'option' : 'free'),
                        comentario: item && item.comentario ? item.comentario : null,
                    };
                },

                hasItem(item) {
                    const normalized = this.normalizeItem(item);
                    const label = normalized.label.toLocaleLowerCase();

                    return this.items.some((selected) => {
                        if (normalized.id && selected.id) {
                            return Number(selected.id) === Number(normalized.id);
                        }

                        return selected.label.toLocaleLowerCase() === label;
                    });
                },

                hasExactResult() {
                    const query = this.normalizedQuery().toLocaleLowerCase();

                    if (!query) {
                        return false;
                    }

                    return this.results.some((result) => result.label.toLocaleLowerCase() === query);
                },

                itemKey(item, index) {
                    return `${item.type || 'item'}:${item.id || item.label}:${index}`;
                },
                    };
                };
            }
        </script>
    @endscript
@endonce
