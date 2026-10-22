@props([
    'eventId',
    'field' => 'titulo',
    'value' => '',
    'placeholder' => 'Sem título',
    'displayClass' => 'text-base font-semibold text-gray-900 dark:text-white',
    'presenceKey' => null,
    'presenceArea' => 'titulo',
])

<div
    x-data="cronogramaInlineText({
        eventId: @js($eventId),
        field: @js($field),
        value: @js($value),
        placeholder: @js($placeholder),
        presenceKey: @js($presenceKey),
        presenceArea: @js($presenceArea),
    })"
    x-on:click.stop
    {{ $attributes->class('min-w-0') }}
>
    <button
        x-show="! editing"
        type="button"
        x-on:click.stop="open()"
        class="block w-full rounded-md px-1.5 py-0.5 text-left transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:hover:bg-gray-700 {{ $displayClass }}"
    >
        <span x-text="value || placeholder"></span>
    </button>

    <div x-show="editing" x-cloak class="relative">
        <input
            x-ref="input"
            x-model="draft"
            type="text"
            class="w-full rounded-md border-gray-300 bg-white px-2 py-1 text-base font-semibold text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            x-on:keydown.enter.prevent="save()"
            x-on:keydown.escape.prevent="cancel()"
            x-on:blur="save()"
        />

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
            if (!window.cronogramaInlineText) {
                window.cronogramaInlineText = function (config) {
                    return {
                eventId: config.eventId,
                field: config.field,
                presenceKey: config.presenceKey || null,
                presenceArea: config.presenceArea || 'titulo',
                value: config.value || '',
                draft: config.value || '',
                placeholder: config.placeholder || '',
                editing: false,
                saving: false,
                saved: false,
                error: '',
                presenceTimer: null,

                open() {
                    this.saved = false;
                    this.error = '';
                    this.draft = this.value;
                    this.editing = true;
                    this.enterPresence();

                    this.$nextTick(() => {
                        this.$refs.input && this.$refs.input.focus();
                        this.$refs.input && this.$refs.input.select();
                    });
                },

                cancel() {
                    this.draft = this.value;
                    this.editing = false;
                    this.error = '';
                    this.leavePresence();
                },

                async save() {
                    if (this.saving || !this.editing) {
                        return;
                    }

                    const nextValue = (this.draft || '').trim();

                    if (!nextValue) {
                        this.cancel();
                        return;
                    }

                    if (nextValue === this.value) {
                        this.editing = false;
                        this.leavePresence();
                        return;
                    }

                    this.saving = true;
                    this.error = '';

                    try {
                        const response = await this.$wire.salvarCampoInlineCronograma(this.eventId, this.field, nextValue);

                        if (response && response.ok === false) {
                            this.error = response.message || 'Não foi possível salvar.';
                            return;
                        }

                        this.value = nextValue;
                        this.editing = false;
                        this.saved = true;
                        this.leavePresence();
                        window.setTimeout(() => this.saved = false, 1200);
                    } catch (error) {
                        this.error = 'Não foi possível salvar.';
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
                    };
                };
            }
        </script>
    @endscript
@endonce
