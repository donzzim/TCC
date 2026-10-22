@php
    /**
     * Layout das páginas de status do EGAP (403, 404, 419).
     *
     * Variáveis esperadas da view filha:
     *   $code       — código HTTP exibido no bloco do endereço
     *   $title      — título principal
     *   $message    — texto explicativo
     *   $pageName   — endereço/recurso solicitado
     *   $homeUrl    — início do painel (fallback quando não há página anterior)
     *   $help       — orientação exibida abaixo das causas
     *
     * Seções:
     *   "icon"   — <path>s do ícone de destaque acima do título (viewBox 24×24)
     *   "causes" — itens <li class="nf-aside__item"> das causas prováveis
     */

    // Sem página anterior (link aberto direto, nova aba) o botão leva ao início.
    $previousUrl = url()->previous($homeUrl);

    $hasPrevious = ! in_array($previousUrl, [url()->current(), $homeUrl], true);

    $occurredAt = now();

    // Só existe sessão quando a rota foi resolvida (ex.: 403, ou 404 de
    // registro inexistente); em rota inexistente o usuário vem nulo.
    $user = auth(\App\Models\egap\User::GUARD)->user();
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} · EGAP</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Mesma regra de tema do Filament: a escolha feita no painel vale aqui também --}}
    <script>
        (function () {
            var theme = 'system';

            try {
                theme = localStorage.getItem('theme') || 'system';
            } catch (e) {}

            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap"
    >

    <style>
        @include('egap.partials.brand-styles')

        /* ===== Ícone de destaque ===== */
        .nf__hero {
            position: relative;
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            margin-bottom: 28px;
            border-radius: 20px;
            color: var(--nf-primary);
            background: var(--nf-primary-soft);
            box-shadow: inset 0 0 0 1px var(--nf-line);
        }

        .nf__hero svg {
            width: 34px;
            height: 34px;
        }

        .nf__hero-code {
            position: absolute;
            right: -10px;
            bottom: -8px;
            padding: 2px 8px;
            border: 2px solid var(--nf-bg);
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--nf-on-primary);
            background: var(--nf-primary);
        }

        /* ===== Endereço solicitado ===== */
        .nf__path {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 32px;
            padding: 14px 18px;
            border: 1px solid var(--nf-line);
            border-left: 3px solid var(--nf-primary);
            border-radius: 10px;
            background: var(--nf-surface);
        }

        .nf__path svg {
            width: 18px;
            height: 18px;
            color: var(--nf-text-muted);
        }

        .nf__url {
            flex: 1;
            min-width: 0;
            font-size: .9375rem;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .nf__link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nf__link svg {
            width: 16px;
            height: 16px;
        }

        .nf-footer__time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nf-footer__time svg {
            width: 14px;
            height: 14px;
        }
    </style>
</head>

<body class="nf-shell">

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

            <form class="nf-header__logout" action="{{ url('/egap/logout') }}" method="POST">
                @csrf
                <button type="submit">Sair</button>
            </form>
        </div>
    @else
        <a class="nf-header__link" href="{{ $homeUrl }}">Ir para o painel</a>
    @endif
</header>

<main class="nf">

    <section>
        <div class="nf__hero">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                @yield('icon')
            </svg>
            <span class="nf__hero-code" aria-label="Erro {{ $code }}">{{ $code }}</span>
        </div>

        <h1 class="nf__title">{{ $title }}</h1>

        <p class="nf__text">{{ $message }}</p>

        <div class="nf__path">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
            <span class="nf__url">{{ $pageName }}</span>
        </div>

        <div class="nf__actions">
            @if ($hasPrevious)
                <a class="nf__btn" href="{{ $previousUrl }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m12 19-7-7 7-7" />
                        <path d="M19 12H5" />
                    </svg>
                    Voltar à página anterior
                </a>

                <a class="nf__link" href="{{ $homeUrl }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <path d="M9 22V12h6v10" />
                    </svg>
                    Ir para o início
                </a>
            @else
                <a class="nf__btn" href="{{ $homeUrl }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <path d="M9 22V12h6v10" />
                    </svg>
                    Ir para o início
                </a>
            @endif
        </div>
    </section>

    <aside class="nf-aside" aria-labelledby="nf-aside-title">
        <h2 class="nf-aside__title" id="nf-aside-title">O que pode ter acontecido</h2>

        <ul class="nf-aside__list">
            @yield('causes')
        </ul>

        <p class="nf-aside__help">{{ $help }}</p>
    </aside>

</main>

<footer class="nf-footer">
    <span>&copy; {{ $occurredAt->year }} EGAP. Acesso restrito a usuários autorizados.</span>
    <span class="nf-footer__time">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
        </svg>
        Ocorrido em <time datetime="{{ $occurredAt->toIso8601String() }}">{{ $occurredAt->format('d/m/Y \à\s H:i') }}</time>
    </span>
</footer>

</body>
</html>
