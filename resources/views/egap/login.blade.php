<x-filament-panels::page.simple>
    @php
        /**
         * Login do EGAP em tela dividida: formulário à esquerda e foto à direita.
         * Usa os mesmos tokens das demais telas de auth (brand-styles), mas não o
         * auth-shell, porque aqui não há cabeçalho, coluna lateral nem rodapé com borda.
         */
        $modulos = ['Patrimônio', 'Almoxarifado', 'Pedidos', 'Agendamento', 'Plano de contratação'];
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap');

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

        /* ===== Tela dividida ===== */
        .lg-split {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.08fr);
            gap: 16px;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 16px;
        }

        .lg-form {
            display: flex;
            flex-direction: column;
            padding: 16px 40px 8px;
        }

        .lg-form__brand {
            display: inline-flex;
            align-self: flex-start;
        }

        .lg-form__body {
            width: 100%;
            max-width: 25rem;
            margin: auto;
            padding: 48px 0;
        }

        .lg-form__title {
            margin: 0;
            font-size: clamp(1.875rem, 3.2vw, 2.375rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.03em;
        }

        .lg-form__text {
            margin: 12px 0 0;
            font-size: 1rem;
            line-height: 1.55;
            color: var(--nf-text-secondary);
        }

        .lg-form__divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 24px;
            font-size: .8125rem;
            color: var(--nf-text-muted);
        }

        .lg-form__divider::before,
        .lg-form__divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--nf-line);
        }

        .lg-form__help {
            margin: 24px 0 0;
            font-size: .875rem;
            line-height: 1.5;
            text-align: center;
            color: var(--nf-text-secondary);
        }

        .lg-form__footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 4px 16px;
            font-size: .8125rem;
            color: var(--nf-text-muted);
        }

        /* Campos e botão do Filament dentro do formulário */
        .lg-form .fi-input-wrp {
            border-radius: 10px;
        }

        .lg-form .fi-input {
            min-height: 44px;
            caret-color: var(--nf-primary);
        }

        .lg-form .fi-btn {
            min-height: 48px;
            border-radius: 10px;
            font-size: .9375rem;
            font-weight: 600;
            box-shadow: 0 8px 20px -10px var(--nf-primary);
        }

        .lg-form .fi-fo-field-label-required-mark,
        .lg-form .fi-fo-field-wrp-error-message,
        .lg-form .fi-fo-field-wrp-error-list {
            color: var(--nf-danger) !important;
        }

        .lg-form .fi-link,
        .lg-form .fi-link-label {
            font-weight: 600;
            color: var(--nf-primary) !important;
        }

        /* ===== Foto ===== */
        .lg-visual {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 24px;
            border-radius: 24px;
            color: #ffffff;
            background: #0f172a url('{{ asset('storage/egap/images/background-login.png') }}') 38% center / cover no-repeat;
        }

        /* Escurece o rodapé da foto para o texto ficar legível sobre as pessoas */
        .lg-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(2, 6, 23, .25) 0%, rgba(2, 6, 23, 0) 22%),
                linear-gradient(0deg, rgba(2, 6, 23, .9) 0%, rgba(2, 6, 23, .55) 32%, rgba(2, 6, 23, 0) 62%);
        }

        .lg-visual > * {
            position: relative;
        }

        .lg-visual__badge {
            align-self: flex-end;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: .8125rem;
            color: #0f172a;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 4px 16px rgba(2, 6, 23, .18);
        }

        .lg-visual__badge svg {
            width: 14px;
            height: 14px;
            color: #2563eb;
        }

        .lg-visual__content {
            max-width: 34rem;
            padding: 0 8px 8px;
        }

        .lg-visual__eyebrow {
            margin: 0;
            font-size: .8125rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #93c5fd;
        }

        .lg-visual__title {
            margin: 12px 0 0;
            font-size: clamp(1.5rem, 2.4vw, 2rem);
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -.015em;
        }

        .lg-visual__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 24px 0 0;
            padding: 0;
            list-style: none;
        }

        .lg-visual__chips li {
            padding: 6px 12px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            font-size: .8125rem;
            font-weight: 500;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(6px);
        }

        @media (max-width: 960px) {
            .lg-split {
                grid-template-columns: minmax(0, 1fr);
                padding: 0;
            }

            .lg-visual {
                display: none;
            }

            .lg-form {
                padding: 24px 20px 16px;
            }
        }
    </style>

    <div class="nf-shell lg-split">

        <section class="lg-form">
            <div class="lg-form__body">
                <h1 class="lg-form__title">Bem-vindo de volta</h1>
                <p class="lg-form__text">Acesse o painel do EGAP para acompanhar patrimônio, almoxarifado, pedidos e agendamentos.</p>

                <div class="lg-form__divider">entre com seu login, CPF ou e-mail</div>

                {{ $this->content }}

                <p class="lg-form__help">Ainda não tem acesso? Os perfis são liberados por módulo pela Administração do EGAP.</p>
            </div>

            <footer class="lg-form__footer">
                <span>&copy; {{ now()->year }} EGAP · Gestão Administrativa Pública</span>
                <span>Acesso restrito a usuários autorizados</span>
            </footer>
        </section>

        <aside class="lg-visual" aria-hidden="true">
            <span class="lg-visual__badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
                Ambiente seguro e restrito
            </span>

            <div class="lg-visual__content">
                <p class="lg-visual__eyebrow">Gestão Administrativa Pública</p>
                <p class="lg-visual__title">Toda a gestão administrativa em um só lugar.</p>

                <ul class="lg-visual__chips">
                    @foreach ($modulos as $modulo)
                        <li>{{ $modulo }}</li>
                    @endforeach
                </ul>
            </div>
        </aside>

    </div>
</x-filament-panels::page.simple>
