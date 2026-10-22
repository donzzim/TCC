<section
    class="fixed z-50 w-[calc(100vw-2rem)] max-w-[390px] pointer-events-none bottom-5 right-4 sm:bottom-6 sm:right-6"
    data-portal-assistant-widget
    data-endpoint="{{ route('portal.assistente.search', [], false) }}"
    aria-label="Assistente EMES">
    <div
        id="portal-assistant-panel"
        class="hidden mb-3 overflow-hidden bg-white border shadow-2xl pointer-events-auto border-slate-200 rounded-lg h-[min(72vh,620px)]"
        data-assistant-panel>
        <div class="flex h-full min-h-0 flex-col">
            <div class="flex items-center justify-between gap-3 px-4 py-3 text-white bg-blue-900">
                <div class="flex items-center min-w-0 gap-2">
                    <i data-lucide="bot" class="w-5 h-5 shrink-0"></i>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold leading-5 truncate">Assistente EMES</h2>
                        <p class="text-xs leading-4 text-blue-100 truncate">Cursos, inscricoes e horarios</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 transition-colors rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/70"
                    data-assistant-close
                    aria-label="Fechar assistente"
                    title="Fechar">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div
                class="flex-1 min-h-0 p-4 space-y-3 overflow-y-auto bg-slate-50"
                data-assistant-messages
                role="log"
                aria-live="polite">
                <div class="max-w-[88%] rounded-lg rounded-tl-sm bg-white px-3 py-2 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-slate-200">
                    Ola. Posso consultar as informacoes publicadas no portal.
                </div>
            </div>

            <div class="px-4 py-3 space-y-2 bg-white border-t border-slate-200" data-assistant-quick-actions>
                <button
                    type="button"
                    class="w-full px-3 py-2 text-sm text-left transition-colors border rounded-md border-slate-200 text-slate-700 hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-700/20"
                    data-assistant-suggestion="Quais cursos estao com inscricao aberta?">
                    Quais cursos estao com inscricao aberta?
                </button>
                <button
                    type="button"
                    class="w-full px-3 py-2 text-sm text-left transition-colors border rounded-md border-slate-200 text-slate-700 hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-700/20"
                    data-assistant-suggestion="Tem curso remoto disponivel?">
                    Tem curso remoto disponivel?
                </button>
            </div>

            <form class="flex items-end gap-2 p-3 bg-white border-t border-slate-200" data-assistant-form>
                <label for="portal-assistant-question" class="sr-only">Pergunta</label>
                <textarea
                    id="portal-assistant-question"
                    class="min-h-11 max-h-28 flex-1 resize-none rounded-md border border-slate-300 px-3 py-2 text-sm leading-5 text-slate-800 placeholder:text-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/20"
                    data-assistant-input
                    rows="1"
                    maxlength="300"
                    placeholder="Digite sua pergunta"></textarea>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center w-11 h-11 text-white transition-colors bg-blue-900 rounded-md shrink-0 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/40 disabled:cursor-not-allowed disabled:bg-slate-400"
                    data-assistant-submit
                    aria-label="Enviar pergunta"
                    title="Enviar">
                    <i data-lucide="send-horizontal" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </div>

    <button
        type="button"
        class="flex items-center justify-center ml-auto text-white transition-all bg-blue-900 rounded-full shadow-xl pointer-events-auto size-14 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-800/30"
        data-assistant-toggle
        aria-controls="portal-assistant-panel"
        aria-expanded="false"
        aria-label="Abrir assistente"
        title="Assistente EMES">
        <i data-lucide="message-circle" class="w-6 h-6"></i>
    </button>
</section>
