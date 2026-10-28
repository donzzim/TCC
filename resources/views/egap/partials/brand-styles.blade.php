{{--
    EGAP · Identidade visual das telas fora do painel (status e auth).
    Fonte única dos tokens, cabeçalho, grid, coluna lateral e rodapé.

    Incluir dentro de um <style>. O tema escuro vem sempre da classe .dark no
    <html>: o Filament a aplica nas telas de auth, e o status-page replica a
    mesma regra (preferência salva em localStorage.theme, senão a do sistema).

    Sem @if/@foreach aqui: dentro de componentes Livewire eles ganham
    comentários <!--[if BLOCK]--> que invalidam as regras CSS vizinhas.
--}}

:root {
    --nf-bg: #f8fafc;
    --nf-surface: #ffffff;
    --nf-text: #0f172a;
    --nf-text-secondary: #475569;
    --nf-text-muted: #64748b;
    --nf-line: #e2e8f0;
    --nf-primary: #2563eb;
    --nf-primary-hover: #1d4ed8;
    --nf-primary-soft: #eff6ff;
    --nf-on-primary: #ffffff;
    --nf-success: #15803d;
    --nf-danger: #dc2626;
    --nf-focus: rgba(37, 99, 235, .35);
}

.dark {
    --nf-bg: #020617;
    --nf-surface: #0b1220;
    --nf-text: #f8fafc;
    --nf-text-secondary: #cbd5e1;
    --nf-text-muted: #94a3b8;
    --nf-line: rgba(148, 163, 184, .18);
    --nf-primary: #3b82f6;
    --nf-primary-hover: #60a5fa;
    --nf-primary-soft: rgba(59, 130, 246, .12);
    --nf-on-primary: #020617;
    --nf-success: #4ade80;
    --nf-danger: #f87171;
    --nf-focus: rgba(96, 165, 250, .45);
}

.nf-shell {
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-height: 100dvh;
    margin: 0;
    font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
    color: var(--nf-text);
    background: var(--nf-bg);
    -webkit-font-smoothing: antialiased;
}

.nf-shell *,
.nf-shell *::before,
.nf-shell *::after {
    box-sizing: border-box;
}

.nf-shell svg {
    flex-shrink: 0;
}

/* ===== Cabeçalho ===== */
.nf-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 32px;
    border-bottom: 1px solid var(--nf-line);
}

.nf-header__brand {
    text-decoration: none;
    color: var(--nf-text);
}

.nf__logo {
    display: block;
    height: 44px;
    width: auto;
}

.nf__logo--light {
    display: none;
}

.dark .nf__logo--dark {
    display: none;
}

.dark .nf__logo--light {
    display: block;
}

.nf-header__link {
    font-size: .875rem;
    font-weight: 600;
    color: var(--nf-text-secondary);
    text-decoration: none;
}

.nf-header__link:hover {
    color: var(--nf-primary);
}

.nf-header__user {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.nf-header__name {
    overflow: hidden;
    font-size: .875rem;
    color: var(--nf-text-muted);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nf-header__name strong {
    font-weight: 600;
    color: var(--nf-text);
}

.nf-header__logout {
    margin: 0;
}

.nf-header__logout button {
    min-height: 36px;
    padding: 0 14px;
    border: 1px solid var(--nf-line);
    border-radius: 8px;
    font: inherit;
    font-size: .875rem;
    font-weight: 600;
    color: var(--nf-text-secondary);
    background: transparent;
    cursor: pointer;
}

.nf-header__logout button:hover {
    color: var(--nf-text);
    background: var(--nf-primary-soft);
}

/* ===== Conteúdo ===== */
.nf {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 20rem);
    gap: 64px;
    align-items: start;
    width: 100%;
    max-width: 64rem;
    margin: auto;
    padding: 64px 32px;
}

.nf__title {
    margin: 0;
    font-size: clamp(2rem, 5vw, 2.75rem);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -.025em;
}

.nf__text {
    max-width: 34rem;
    margin: 16px 0 0;
    font-size: 1.0625rem;
    line-height: 1.6;
    color: var(--nf-text-secondary);
}

/* Ações */
.nf__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px 24px;
    margin-top: 32px;
}

.nf__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: .9375rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--nf-on-primary);
    background: var(--nf-primary);
    transition: background-color .15s ease;
}

.nf__btn:hover {
    background: var(--nf-primary-hover);
}

.nf__btn svg {
    width: 18px;
    height: 18px;
}

.nf__link {
    font-size: .9375rem;
    font-weight: 600;
    color: var(--nf-text-secondary);
    text-decoration: underline;
    text-decoration-color: var(--nf-line);
    text-underline-offset: 4px;
}

.nf__link:hover {
    color: var(--nf-text);
    text-decoration-color: currentColor;
}

/* ===== Coluna lateral (causas, orientações) ===== */
.nf-aside {
    padding-left: 32px;
    border-left: 1px solid var(--nf-line);
}

.nf-aside__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}

.nf-aside__list {
    margin: 20px 0 0;
    padding: 0;
    list-style: none;
}

.nf-aside__item {
    display: flex;
    gap: 14px;
}

.nf-aside__item + .nf-aside__item {
    margin-top: 20px;
}

.nf-aside__icon {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: var(--nf-primary);
    background: var(--nf-primary-soft);
}

.nf-aside__icon svg {
    width: 16px;
    height: 16px;
}

.nf-aside__name {
    margin: 0;
    font-size: .9375rem;
    font-weight: 600;
    line-height: 1.4;
}

.nf-aside__desc {
    margin: 4px 0 0;
    font-size: .875rem;
    line-height: 1.5;
    color: var(--nf-text-secondary);
}

.nf-aside__help {
    margin: 28px 0 0;
    padding-top: 20px;
    border-top: 1px solid var(--nf-line);
    font-size: .875rem;
    line-height: 1.5;
    color: var(--nf-text-secondary);
}

/* ===== Rodapé ===== */
.nf-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px 24px;
    padding: 20px 32px;
    border-top: 1px solid var(--nf-line);
    font-size: .8125rem;
    color: var(--nf-text-muted);
}

/* ===== Foco e acessibilidade ===== */
.nf-shell a:focus-visible,
.nf-shell button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px var(--nf-focus);
    border-radius: 10px;
}

@media (prefers-reduced-motion: reduce) {
    .nf__btn {
        transition: none;
    }
}

@media (max-width: 860px) {
    .nf {
        grid-template-columns: minmax(0, 1fr);
        gap: 48px;
        padding: 48px 20px;
    }

    .nf-aside {
        padding: 32px 0 0;
        border-left: 0;
        border-top: 1px solid var(--nf-line);
    }

    .nf-header,
    .nf-footer {
        padding-left: 20px;
        padding-right: 20px;
    }
}

@media (max-width: 600px) {
    .nf-header__prefix {
        display: none;
    }
}

@media (max-width: 480px) {
    .nf__logo {
        height: 36px;
    }

    .nf__btn {
        width: 100%;
    }
}
