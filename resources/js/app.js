function refreshLucideIcons() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== undefined && text !== null) {
        element.textContent = text;
    }

    return element;
}

function createIcon(name, className = 'w-4 h-4') {
    const icon = document.createElement('i');
    icon.setAttribute('data-lucide', name);
    icon.className = className;

    return icon;
}

function appendMeta(container, iconName, value) {
    if (!value) {
        return;
    }

    const item = createElement('div', 'flex items-start gap-2 text-xs leading-5 text-slate-600');
    item.appendChild(createIcon(iconName, 'mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-900'));
    item.appendChild(createElement('span', '', value));
    container.appendChild(item);
}

function initPortalAssistantWidget() {
    const widget = document.querySelector('[data-portal-assistant-widget]');

    if (!widget) {
        return;
    }

    const endpoint = widget.dataset.endpoint;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const panel = widget.querySelector('[data-assistant-panel]');
    const toggle = widget.querySelector('[data-assistant-toggle]');
    const close = widget.querySelector('[data-assistant-close]');
    const form = widget.querySelector('[data-assistant-form]');
    const input = widget.querySelector('[data-assistant-input]');
    const submit = widget.querySelector('[data-assistant-submit]');
    const messages = widget.querySelector('[data-assistant-messages]');
    const quickActions = widget.querySelector('[data-assistant-quick-actions]');

    if (!endpoint || !panel || !toggle || !form || !input || !submit || !messages) {
        return;
    }

    let loadingMessage = null;

    const scrollMessages = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    const setOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            input.focus();
            scrollMessages();
        }
    };

    const setLoading = (loading) => {
        input.disabled = loading;
        submit.disabled = loading;

        if (loading) {
            loadingMessage = createElement(
                'div',
                'max-w-[88%] rounded-lg rounded-tl-sm bg-white px-3 py-2 text-sm leading-6 text-slate-600 shadow-sm ring-1 ring-slate-200',
                'Consultando...'
            );
            messages.appendChild(loadingMessage);
            scrollMessages();
            return;
        }

        loadingMessage?.remove();
        loadingMessage = null;
    };

    const appendBubble = (text, type = 'assistant') => {
        const isUser = type === 'user';
        const bubble = createElement(
            'div',
            isUser
                ? 'ml-auto max-w-[88%] rounded-lg rounded-tr-sm bg-blue-900 px-3 py-2 text-sm leading-6 text-white shadow-sm'
                : 'max-w-[88%] rounded-lg rounded-tl-sm bg-white px-3 py-2 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-slate-200',
            text
        );

        messages.appendChild(bubble);
        scrollMessages();
    };

    const appendResultCard = (course) => {
        const card = createElement(
            'article',
            'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm'
        );

        const body = createElement('div', 'space-y-3 p-3');
        const title = createElement('a', 'block text-sm font-semibold leading-5 text-blue-900 hover:text-blue-700');
        title.href = course.url;
        title.textContent = course.title || 'Curso';

        const description = createElement(
            'p',
            'text-xs leading-5 text-slate-600',
            course.description || 'Informacoes disponiveis na pagina do curso.'
        );

        const meta = createElement('div', 'grid gap-1.5');
        appendMeta(meta, 'calendar-days', course.period?.label);
        appendMeta(meta, 'clock-3', course.turn);
        appendMeta(meta, 'map-pin', course.location);
        appendMeta(meta, 'monitor', course.modality);
        appendMeta(meta, 'users', course.public_target?.join(', '));
        appendMeta(meta, 'list-checks', course.activity_count ? `${course.activity_count} atividade(s)` : null);
        appendMeta(meta, 'calendar-clock', course.registration_period?.label ? `Inscricoes: ${course.registration_period.label}` : null);

        let activitiesBlock = null;

        if (course.activities?.length) {
            activitiesBlock = createElement('div', 'rounded-md bg-slate-50 p-2 text-xs leading-5 text-slate-600');
            activitiesBlock.appendChild(createElement('div', 'mb-1 font-medium text-slate-800', 'Atividades'));

            course.activities.slice(0, 4).forEach((activity) => {
                const row = createElement('div', 'flex gap-1.5');
                row.appendChild(createElement('span', 'text-blue-900', '-'));
                row.appendChild(createElement('span', '', `${activity.title || 'Atividade'}${activity.period?.label ? `: ${activity.period.label}` : ''}`));
                activitiesBlock.appendChild(row);
            });
        }

        const actions = createElement('div', 'flex flex-wrap gap-2 pt-1');
        const details = createElement(
            'a',
            'inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50'
        );
        details.href = course.url;
        details.appendChild(createIcon('external-link', 'h-3.5 w-3.5'));
        details.appendChild(createElement('span', '', 'Ver curso'));
        actions.appendChild(details);

        if (course.registration_open && course.registration_url) {
            const registration = createElement(
                'a',
                'inline-flex items-center gap-1.5 rounded-md bg-blue-900 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-800'
            );
            registration.href = course.registration_url;
            registration.appendChild(createIcon('clipboard-check', 'h-3.5 w-3.5'));
            registration.appendChild(createElement('span', '', 'Inscricao'));
            actions.appendChild(registration);
        }

        body.appendChild(title);
        body.appendChild(description);
        body.appendChild(meta);
        if (activitiesBlock) {
            body.appendChild(activitiesBlock);
        }
        body.appendChild(actions);
        card.appendChild(body);
        messages.appendChild(card);
    };

    const renderSuggestions = (suggestions = []) => {
        if (!quickActions || suggestions.length === 0) {
            return;
        }

        quickActions.innerHTML = '';

        suggestions.slice(0, 3).forEach((suggestion) => {
            const button = createElement(
                'button',
                'w-full rounded-md border border-slate-200 px-3 py-2 text-left text-sm text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-700/20',
                suggestion
            );
            button.type = 'button';
            button.dataset.assistantSuggestion = suggestion;
            quickActions.appendChild(button);
        });
    };

    const submitQuestion = async (question) => {
        const text = question.trim();

        if (!text) {
            return;
        }

        appendBubble(text, 'user');
        input.value = '';
        input.style.height = 'auto';
        setLoading(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    pergunta: text,
                    limit: 5,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Nao foi possivel consultar agora.');
            }

            setLoading(false);
            appendBubble(payload.message || 'Consulta concluida.');
            (payload.results || []).forEach(appendResultCard);
            renderSuggestions(payload.suggestions || []);
            refreshLucideIcons();
            scrollMessages();
        } catch (error) {
            setLoading(false);
            appendBubble(error.message || 'Nao foi possivel consultar agora.');
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.classList.contains('hidden'));
    });

    close?.addEventListener('click', () => {
        setOpen(false);
    });

    quickActions?.addEventListener('click', (event) => {
        const target = event.target.closest('[data-assistant-suggestion]');

        if (!target) {
            return;
        }

        setOpen(true);
        submitQuestion(target.dataset.assistantSuggestion || target.textContent || '');
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitQuestion(input.value);
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 112)}px`;
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
}

document.addEventListener('DOMContentLoaded', initPortalAssistantWidget);
