@php
    $lotacao = $this->lotacaoAtual();
    $unidade = $lotacao?->unidadeJudiciaria?->unidadeorganizacional ?? $lotacao?->unidadeJudiciaria?->setor;
    $setor = $lotacao?->setorRef?->setor;
    $pendencias = $this->pendencias();
    $totalPendente = array_sum(array_column($pendencias, 'valor'));
    $grupos = $this->gruposDeAcesso();
@endphp

<x-filament-panels::page>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..800&display=swap" rel="stylesheet">

    <style>
        .eh {
            --eh-titulo: #13294d;
            --eh-azul: #2563eb;
            --eh-ambar: #d97706;
            --eh-linha: #dbe2ec;
            --eh-texto: #1e293b;
            --eh-texto-2: #5b6779;
            --eh-realce: #eef3fb;
            --eh-furo: var(--gray-50, #f9fafb);
            --eh-placa: #fff;
            --eh-placa-borda: #bfd0ee;
            --eh-placa-linha: #e2e8f0;
            --eh-anel: #dbe7fb;
            --eh-canhoto: #f5f8fd;
            --eh-zero: #b6c0cd;
            --eh-display: "Archivo", "Inter", ui-sans-serif, system-ui, sans-serif;
            color: var(--eh-texto);
        }

        .dark .eh {
            --eh-titulo: #fff;
            --eh-azul: #60a5fa;
            --eh-ambar: #fbbf24;
            --eh-linha: rgba(255, 255, 255, .09);
            --eh-texto: #e5e9f0;
            --eh-texto-2: #97a3b6;
            --eh-realce: rgba(96, 165, 250, .08);
            --eh-furo: var(--gray-950, #030712);
            --eh-placa: #0b1220;
            --eh-placa-borda: rgba(96, 165, 250, .35);
            --eh-placa-linha: rgba(255, 255, 255, .08);
            --eh-anel: rgba(96, 165, 250, .15);
            --eh-canhoto: rgba(96, 165, 250, .05);
            --eh-zero: rgba(255, 255, 255, .3);
        }

        .eh-placa {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(16rem, 22rem);
            border-radius: 1.25rem;
            background: var(--eh-placa);
            border: 1.5px solid var(--eh-placa-borda);
            color: var(--eh-texto);
            overflow: hidden;
            transform-origin: 2.75rem 2.75rem;
            animation: eh-pendura .9s cubic-bezier(.3, 1.6, .5, 1) both;
        }

        @keyframes eh-pendura {
            from { transform: rotate(-1.6deg); }
            to { transform: rotate(0); }
        }

        .eh-placa::before {
            content: "";
            position: absolute;
            top: 1.75rem;
            left: 1.75rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: var(--eh-furo);
            box-shadow: inset 0 2px 3px rgba(0, 0, 0, .3), 0 0 0 4px var(--eh-anel);
        }

        .eh-corpo {
            padding: 4.25rem 2.5rem 2.25rem;
            min-width: 0;
        }

        .eh-marca {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-family: var(--eh-display);
            font-stretch: 118%;
            font-weight: 700;
            font-size: .8125rem;
            letter-spacing: .04em;
            color: var(--eh-texto-2);
        }

        .eh-engrenagem {
            width: 1rem;
            height: 1rem;
            color: var(--eh-ambar);
        }

        .eh-saudacao {
            margin-top: 1.25rem;
            font-family: var(--eh-display);
            font-stretch: 112%;
            font-weight: 800;
            font-size: clamp(2rem, 1.2rem + 3vw, 3.5rem);
            line-height: 1.02;
            letter-spacing: -.02em;
            overflow-wrap: anywhere;
            color: var(--eh-titulo);
        }

        .eh-lotacao {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem 2.5rem;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--eh-placa-linha);
        }

        .eh-campo dt {
            font-size: .75rem;
            color: var(--eh-texto-2);
        }

        .eh-campo dd {
            margin-top: .15rem;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .eh-canhoto {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 2rem 1.75rem 1.75rem 2rem;
            background: var(--eh-canhoto);
            border-left: 2px dashed var(--eh-placa-borda);
        }

        .eh-canhoto::before,
        .eh-canhoto::after {
            content: "";
            position: absolute;
            left: -0.8rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            background: var(--eh-furo);
            box-shadow: inset 0 0 0 1.5px var(--eh-placa-borda);
        }

        .eh-canhoto::before { top: -0.75rem; }
        .eh-canhoto::after { bottom: -0.75rem; }

        .eh-canhoto-titulo {
            font-family: var(--eh-display);
            font-stretch: 112%;
            font-weight: 700;
            font-size: 1rem;
            color: var(--eh-titulo);
        }

        .eh-canhoto-resumo {
            margin-top: .2rem;
            font-size: .8125rem;
            color: var(--eh-texto-2);
        }

        .eh-pendencias {
            margin-top: 1.25rem;
            display: grid;
            gap: .25rem;
        }

        .eh-pendencia {
            display: grid;
            grid-template-columns: 3rem minmax(0, 1fr) auto;
            align-items: center;
            gap: .75rem;
            padding: .55rem .6rem .55rem 0;
            border-radius: .6rem;
            color: var(--eh-texto);
            text-decoration: none;
        }

        .eh-pendencia:hover { background: var(--eh-realce); }

        .eh-pendencia:focus-visible,
        .eh-servico:focus-visible {
            outline: 2px solid var(--eh-azul);
            outline-offset: 2px;
        }

        .eh-numero {
            font-family: var(--eh-display);
            font-stretch: 100%;
            font-weight: 800;
            font-size: 1.75rem;
            line-height: 1;
            text-align: right;
            font-variant-numeric: tabular-nums;
            color: var(--eh-zero);
        }

        .eh-pendencia.tem .eh-numero { color: var(--eh-ambar); }

        .eh-pendencia-nome {
            font-size: .875rem;
            line-height: 1.3;
        }

        .eh-pendencia-icone {
            width: 1rem;
            height: 1rem;
            color: var(--eh-texto-2);
        }

        .eh-indice {
            margin-top: 3rem;
        }

        .eh-grupo {
            display: grid;
            grid-template-columns: 14rem minmax(0, 1fr);
            gap: 2rem;
            padding: 2rem 0;
            border-top: 1px solid var(--eh-linha);
        }

        .eh-grupo-cabeca {
            align-self: start;
            position: sticky;
            top: 5rem;
        }

        .eh-grupo-nome {
            font-family: var(--eh-display);
            font-stretch: 112%;
            font-weight: 700;
            font-size: 1.25rem;
            line-height: 1.2;
            color: var(--eh-texto);
        }

        .eh-grupo-qtd {
            margin-top: .3rem;
            font-size: .8125rem;
            color: var(--eh-texto-2);
        }

        .eh-servicos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
            gap: .25rem 1.5rem;
        }

        .eh-servico {
            display: grid;
            grid-template-columns: 2.25rem minmax(0, 1fr);
            gap: .9rem;
            padding: .85rem .9rem .85rem .6rem;
            border-radius: .75rem;
            text-decoration: none;
            color: inherit;
            transition: background-color .15s ease;
        }

        .eh-servico:hover { background: var(--eh-realce); }

        .eh-servico-icone {
            display: grid;
            place-items: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .55rem;
            border: 1px solid var(--eh-linha);
            color: var(--eh-azul);
        }

        .eh-servico-icone svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .eh-servico-nome {
            display: block;
            font-weight: 600;
            font-size: .9375rem;
            line-height: 1.3;
            color: var(--eh-texto);
        }

        .eh-servico:hover .eh-servico-nome { color: var(--eh-azul); }

        .eh-servico-desc {
            display: block;
            margin-top: .2rem;
            font-size: .8125rem;
            line-height: 1.45;
            color: var(--eh-texto-2);
            max-width: 46ch;
        }

        @media (max-width: 1023px) {
            .eh-placa { grid-template-columns: minmax(0, 1fr); }

            .eh-canhoto {
                border-left: 0;
                border-top: 2px dashed var(--eh-placa-borda);
                padding: 1.75rem 1.5rem;
            }

            .eh-canhoto::before,
            .eh-canhoto::after {
                top: -0.8rem;
                bottom: auto;
            }

            .eh-canhoto::before { left: -0.75rem; }
            .eh-canhoto::after { left: auto; right: -0.75rem; }

            .eh-grupo {
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
            }

            .eh-grupo-cabeca { position: static; }
        }

        @media (max-width: 639px) {
            .eh-corpo { padding: 3.75rem 1.5rem 1.75rem; }
            .eh-placa::before { top: 1.4rem; left: 1.4rem; }
            .eh-servicos { grid-template-columns: minmax(0, 1fr); }
        }

        @media (prefers-reduced-motion: reduce) {
            .eh-placa { animation: none; }
            .eh-servico { transition: none; }
        }
    </style>

    <div class="eh">
        <section class="eh-placa" aria-label="Identificação">
            <div class="eh-corpo">
                <p class="eh-marca">
                    <svg class="eh-engrenagem" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M10.3 2h3.4l.5 2.6a8 8 0 0 1 1.9.8l2.2-1.5 2.4 2.4-1.5 2.2c.3.6.6 1.2.8 1.9l2.6.5v3.4l-2.6.5a8 8 0 0 1-.8 1.9l1.5 2.2-2.4 2.4-2.2-1.5c-.6.3-1.2.6-1.9.8l-.5 2.6h-3.4l-.5-2.6a8 8 0 0 1-1.9-.8l-2.2 1.5-2.4-2.4 1.5-2.2a8 8 0 0 1-.8-1.9L2 13.7v-3.4l2.6-.5c.2-.7.5-1.3.8-1.9L3.9 5.7l2.4-2.4 2.2 1.5c.6-.3 1.2-.6 1.9-.8L10.3 2Zm1.7 6.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>
                    </svg>
                    EGAP Ambiente Externo
                </p>

                <h1 class="eh-saudacao">{{ $this->saudacao() }}, {{ $this->primeiroNomeUsuario() }}.</h1>

                @if ($unidade || $setor)
                    <dl class="eh-lotacao">
                        @if ($unidade)
                            <div class="eh-campo">
                                <dt>Unidade</dt>
                                <dd>{{ $unidade }}</dd>
                            </div>
                        @endif

                        @if ($setor)
                            <div class="eh-campo">
                                <dt>Setor</dt>
                                <dd>{{ $setor }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>

            <aside class="eh-canhoto" aria-labelledby="eh-canhoto-titulo">
                <h2 id="eh-canhoto-titulo" class="eh-canhoto-titulo">Seus pedidos em análise</h2>
                <p class="eh-canhoto-resumo">
                    @if ($totalPendente === 0)
                        Nenhum pedido aguardando atendimento.
                    @elseif ($totalPendente === 1)
                        1 pedido aguardando atendimento.
                    @else
                        {{ $totalPendente }} pedidos aguardando atendimento.
                    @endif
                </p>

                <div class="eh-pendencias">
                    @foreach ($pendencias as $pendencia)
                        <a href="{{ $pendencia['url'] }}"
                           class="eh-pendencia {{ $pendencia['valor'] > 0 ? 'tem' : '' }}"
                           aria-label="{{ $pendencia['label'] }}: {{ $pendencia['valor'] }}">
                            <span class="eh-numero">{{ $pendencia['valor'] }}</span>
                            <span class="eh-pendencia-nome">{{ $pendencia['assunto'] }}</span>
                            <x-filament::icon :icon="$pendencia['icon']" class="eh-pendencia-icone"/>
                        </a>
                    @endforeach
                </div>
            </aside>
        </section>

        <div class="eh-indice">
            @foreach ($grupos as $grupo => $links)
                <section class="eh-grupo" aria-labelledby="eh-grupo-{{ $loop->index }}">
                    <header class="eh-grupo-cabeca">
                        <h2 id="eh-grupo-{{ $loop->index }}" class="eh-grupo-nome">{{ $grupo }}</h2>
                        <p class="eh-grupo-qtd">
                            {{ count($links) }} {{ count($links) === 1 ? 'serviço' : 'serviços' }}
                        </p>
                    </header>

                    <div class="eh-servicos">
                        @foreach ($links as $link)
                            <a href="{{ $link['url'] }}" class="eh-servico">
                                <span class="eh-servico-icone">
                                    <x-filament::icon :icon="$link['icon']"/>
                                </span>

                                <span>
                                    <span class="eh-servico-nome">{{ $link['label'] }}</span>
                                    <span class="eh-servico-desc">{{ $link['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
