@php
    /**
     * Moldura das telas de autenticação do EGAP (login, recuperar/redefinir
     * senha, verificação de e-mail). Mesma identidade das páginas de status,
     * mas rodando dentro do Filament — por isso o tema escuro vem da classe .dark.
     *
     * Uso (dentro de <x-filament-panels::page.simple>):
     *   @component('egap.layouts.auth-shell', [...]) formulário @endcomponent
     *
     * Variáveis:
     *   $title       — título principal
     *   $text        — texto explicativo (opcional)
     *   $asideTitle  — título da coluna lateral
     *   $asideItems  — lista de ['icon' => '<path …/>', 'name' => …, 'desc' => …]
     *   $help        — orientação no fim da coluna lateral (opcional)
     *   $headerLink  — link do canto superior para visitantes (opcional)
     *   $headerLabel — texto desse link
     *
     * Slots: $slot (formulário, dentro do painel) e $actions (abaixo do painel, opcional).
     */

    $user = filament()->auth()->user();

    $homeUrl = filament()->getUrl();
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap');

    /* ===== Reset do layout padrão do Filament (simple page) ===== */
    .fi-simple-layout {
        background: transparent !important;
    }

    .fi-simple-layout-header,
    .fi-simple-header,
    .fi-logo {
        display: none !important;
    }

    .fi-simple-main-ctn {
        display: block !important;
        width: 100% !important;
        min-height: 100vh !important;
        padding: 0 !important;
    }

    .fi-simple-main {
        width: 100% !important;
        max-width: none !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .fi-simple-page,
    .fi-simple-page-content {
        display: block !important;
        padding: 0 !important;
    }

    @include('egap.partials.brand-styles')

    /* ===== Painel do formulário ===== */
    .nf-panel {
        max-width: 28rem;
        margin-top: 32px;
        padding: 28px;
        border: 1px solid var(--nf-line);
        border-left: 3px solid var(--nf-primary);
        border-radius: 12px;
        background: var(--nf-surface);
    }

    .nf-panel .fi-btn {
        min-height: 44px;
        border-radius: 10px;
        font-weight: 600;
    }

    .nf-panel .fi-input-wrp {
        border-radius: 10px;
    }

    .nf-panel .fi-input {
        min-height: 42px;
        caret-color: var(--nf-primary);
    }

    .nf-panel .fi-fo-field-label-required-mark,
    .nf-panel .fi-fo-field-wrp-error-message,
    .nf-panel .fi-fo-field-wrp-error-list {
        color: var(--nf-danger) !important;
    }

    .nf-panel .fi-link,
    .nf-panel .fi-link-label {
        color: var(--nf-primary) !important;
    }

    .nf-panel .fi-sc-text {
        color: var(--nf-text-secondary);
        line-height: 1.6;
    }

    .nf-auth__actions .fi-link {
        font-size: .9375rem;
        font-weight: 600;
        color: var(--nf-text-secondary);
    }

    .nf-auth__actions .fi-link:hover {
        color: var(--nf-text);
    }

    @media (max-width: 480px) {
        .nf-panel {
            padding: 22px 18px;
        }
    }
</style>

<div class="nf-shell">

    <header class="nf-header">
        <a class="nf-header__brand" href="{{ $homeUrl }}" aria-label="EGAP — ir para o início">
            <img
                class="nf__logo nf__logo--dark"
                src="{{ asset('storage/egap/brand-dark.png') }}"
                alt="EGAP — Gestão Administrativa Pública"
            >
            <img
                class="nf__logo nf__logo--light"
                src="{{ asset('storage/egap/brand-light.png') }}"
                alt="EGAP — Gestão Administrativa Pública"
            >
        </a>

        @if ($user)
            <div class="nf-header__user">
                <span class="nf-header__name"><span class="nf-header__prefix">Conectado como </span><strong>{{ $user->name }}</strong></span>

                <form class="nf-header__logout" action="{{ filament()->getLogoutUrl() }}" method="POST">
                    @csrf
                    <button type="submit">Sair</button>
                </form>
            </div>
        @elseif (filled($headerLink ?? null))
            <a class="nf-header__link" href="{{ $headerLink }}">{{ $headerLabel }}</a>
        @endif
    </header>

    <div class="nf">

        <section>
            <h1 class="nf__title">{{ $title }}</h1>

            @if (filled($text ?? null))
                <p class="nf__text">{{ $text }}</p>
            @endif

            <div class="nf-panel">
                {{ $slot }}
            </div>

            @if (isset($actions) && $actions->isNotEmpty())
                <div class="nf__actions nf-auth__actions">
                    {{ $actions }}
                </div>
            @endif
        </section>

        <aside class="nf-aside" aria-labelledby="nf-aside-title">
            <h2 class="nf-aside__title" id="nf-aside-title">{{ $asideTitle }}</h2>

            <ul class="nf-aside__list">
                @foreach ($asideItems as $item)
                    <li class="nf-aside__item">
                        <span class="nf-aside__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $item['icon'] !!}
                            </svg>
                        </span>
                        <div>
                            <p class="nf-aside__name">{{ $item['name'] }}</p>
                            <p class="nf-aside__desc">{{ $item['desc'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if (filled($help ?? null))
                <p class="nf-aside__help">{{ $help }}</p>
            @endif
        </aside>

    </div>

    <footer class="nf-footer">
        <span>&copy; {{ now()->year }} EGAP. Acesso restrito a usuários autorizados.</span>
        <span>Gestão Administrativa Pública</span>
    </footer>

</div>
