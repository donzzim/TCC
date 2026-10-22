{{--
    Layout base de todas as impressões do e-GAPes (relatórios, termos, pedidos, etc.).
    Centraliza fontes, cores, cabeçalho institucional, rodapé e regras de impressão,
    para que todo documento emitido pelo sistema tenha a mesma identidade visual.
--}}
@props([
    'titulo',
    'tituloAba' => null,
    'secao' => null,
    'kicker' => null,
    'identificador' => null,
    'orientacao' => 'retrato',
    'dataEmissao' => null,
    'rodape' => null,
    'rodapeDireita' => null,
    'imprimirAoCarregar' => true,
    'brasaoEmbutido' => false,
])

@php
    $retrato = $orientacao === 'retrato';
    $emissao = $dataEmissao ?? now()->format('d/m/Y');
    $secao ??= 'e-GAPes';
    $rodape ??= 'e-GAPes · ' . $secao;
    $rodapeDireita ??= $identificador ?? $titulo;

    // Documentos arquivados (ex.: termos gravados em HTML) precisam do brasão embutido
    // para não depender da URL da aplicação no momento da visualização.
    $brasao = asset('images/brasao-tjes.png');
    if ($brasaoEmbutido && is_file($caminhoBrasao = public_path('images/brasao-tjes.png'))) {
        $brasao = 'data:image/png;base64,' . base64_encode(file_get_contents($caminhoBrasao));
    }
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tituloAba ?? $titulo }}</title>

    <style>
        :root {
            color-scheme: only light;
            --ink: #1b2733;
            --muted: #66717d;
            --line: #d7dde3;
            --line-strong: #aeb8c2;
            --surface: #ffffff;
            --surface-soft: #f5f7f9;
            --brand: #173f63;
            --brand-dark: #0f304d;
            --brand-soft: #e9eef3;
            --accent: #b38a35;
            --accent-soft: #f7f4ec;
            --danger: #8a2330;
            --danger-soft: #fbf0f1;
            --paper-width: {{ $retrato ? '210mm' : '297mm' }};
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            background: #ffffff;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        body {
            padding: 24px;
        }

        button {
            font: inherit;
        }

        /* ===== Barra de ações (fora da impressão) ===== */
        .doc-toolbar {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: var(--paper-width);
            margin: 0 auto 12px;
        }

        .doc-print-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid var(--brand);
            border-radius: 6px;
            background: var(--brand);
            color: #ffffff;
            cursor: pointer;
            font-weight: 700;
        }

        .doc-print-button:hover {
            background: var(--brand-dark);
        }

        .doc-print-button:focus-visible {
            outline: 3px solid rgba(23, 63, 99, 0.25);
            outline-offset: 2px;
        }

        .doc-print-button svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        /* ===== Folha ===== */
        .doc-page {
            width: 100%;
            max-width: var(--paper-width);
            min-width: 0;
            min-height: {{ $retrato ? '270mm' : '190mm' }};
            margin: 0 auto;
            padding: 12mm;
            border: 1px solid var(--line);
            background: var(--surface);
        }

        /* ===== Cabeçalho institucional ===== */
        .doc-header {
            display: grid;
            grid-template-columns: 62px minmax(0, 1fr) auto;
            align-items: center;
            gap: 15px;
            padding-bottom: 16px;
            border-bottom: 3px solid var(--brand);
            position: relative;
        }

        .doc-header::after {
            position: absolute;
            right: 0;
            bottom: -3px;
            width: 76px;
            height: 3px;
            background: var(--accent);
            content: '';
        }

        .doc-logo {
            display: block;
            width: 56px;
            height: 56px;
            object-fit: contain;
        }

        .doc-institution {
            min-width: 0;
        }

        .doc-institution-name {
            margin: 0 0 3px;
            color: var(--brand);
            font-size: 16px;
            font-weight: 800;
            line-height: 1.2;
        }

        .doc-institution-unit {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .doc-emission {
            min-width: 110px;
            text-align: right;
        }

        .doc-meta-label {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .doc-meta-value {
            font-size: 10px;
            font-weight: 700;
        }

        /* ===== Título do documento ===== */
        .doc-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 0 16px;
        }

        .doc-kicker {
            display: block;
            margin-bottom: 4px;
            color: var(--accent);
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .doc-heading h1 {
            margin: 0;
            color: var(--ink);
            font-size: 20px;
            line-height: 1.2;
        }

        .doc-badge {
            flex: 0 0 auto;
            padding: 8px 11px;
            border: 1px solid var(--line-strong);
            border-radius: 4px;
            background: var(--surface-soft);
            color: var(--brand);
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        /* ===== Blocos de conteúdo ===== */
        h2.bloco {
            margin: 18px 0 8px;
            color: var(--brand);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .doc-body > h2.bloco:first-child {
            margin-top: 0;
        }

        .doc-body {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
        }

        /* Campos rótulo/valor (dados do documento, filtros aplicados) */
        .doc-campos {
            display: grid;
            grid-template-columns: repeat(var(--colunas, 2), minmax(0, 1fr));
            margin: 0 0 18px;
            border-top: 1px solid var(--line);
            border-left: 1px solid var(--line);
            background: var(--surface-soft);
        }

        .doc-campo {
            min-width: 0;
            margin: 0;
            padding: 8px 11px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .doc-campo.largo {
            grid-column: 1 / -1;
        }

        .doc-campos.em-linha {
            display: flex;
            flex-wrap: wrap;
        }

        .doc-campos.em-linha .doc-campo {
            min-width: 220px;
        }

        .doc-campo-label,
        .doc-campo dt {
            display: block;
            margin: 0 0 2px;
            color: var(--muted);
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .doc-campo-valor,
        .doc-campo dd {
            display: block;
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 10px;
            font-weight: 700;
        }

        .doc-declaracao {
            margin: 0 0 18px;
            font-size: 11px;
            line-height: 1.55;
            text-align: justify;
        }

        .doc-nota {
            margin: 18px 0 0;
            padding: 10px 13px;
            border-left: 3px solid var(--accent);
            background: var(--surface-soft);
            color: #3d4853;
            font-size: 10px;
            line-height: 1.5;
        }

        .doc-aviso {
            margin: 0 0 18px;
            padding: 10px 13px;
            border: 1px solid #e6c3c8;
            border-left: 3px solid var(--danger);
            background: var(--danger-soft);
            color: var(--danger);
            font-size: 10px;
            line-height: 1.5;
        }

        .doc-aviso strong:first-child {
            display: block;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .doc-aviso p {
            margin: 4px 0 0;
            color: var(--ink);
            text-align: justify;
        }

        /* ===== Tabelas ===== */
        .report-table-wrap,
        .doc-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table.rel {
            width: 100%;
            border-collapse: collapse;
            color: var(--ink);
            font-size: 9px;
            font-variant-numeric: tabular-nums;
        }

        table.rel.confortavel {
            font-size: 10px;
        }

        table.rel th,
        table.rel td {
            border: 1px solid var(--line);
            padding: 6px 7px;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        table.rel.confortavel th,
        table.rel.confortavel td {
            padding: 7px 8px;
        }

        table.rel th {
            background: var(--brand);
            color: #ffffff;
            font-size: 8px;
            font-weight: 800;
            line-height: 1.25;
            text-transform: uppercase;
        }

        table.rel tbody tr:not(.grupo):not(.subtotal):not(.total):nth-child(even) td {
            background: var(--surface-soft);
        }

        table.rel tbody tr:not(.grupo):not(.subtotal):not(.total):hover td {
            background: #eef3f7;
        }

        table.rel tr.grupo td {
            padding: 7px;
            border-top: 2px solid var(--brand);
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 800;
            text-transform: uppercase;
        }

        table.rel tr.subtotal td {
            background: var(--accent-soft);
            font-weight: 700;
        }

        table.rel tr.total td {
            border-top: 2px solid var(--brand);
            background: #ffffff;
            color: var(--brand);
            font-weight: 800;
        }

        table.rel td.rotulo {
            width: 18%;
            background: var(--surface-soft);
            color: var(--muted);
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .a-right {
            text-align: right;
        }

        .a-center {
            text-align: center;
        }

        .a-left {
            text-align: left;
        }

        .nowrap {
            white-space: nowrap;
        }

        .forte {
            font-weight: 700;
        }

        .vazio {
            padding: 22px !important;
            color: var(--muted);
            font-style: normal;
            text-align: center;
        }

        .quebra {
            break-after: page;
            page-break-after: always;
        }

        /* ===== Assinaturas ===== */
        .doc-assinaturas {
            display: grid;
            grid-template-columns: repeat(var(--colunas, 2), minmax(0, 1fr));
            gap: 36px;
            margin-top: 44px;
            break-inside: avoid;
        }

        .doc-assinatura {
            min-width: 0;
            text-align: center;
        }

        .doc-assinatura-caixa {
            height: 64px;
            margin-bottom: 6px;
            border: 1px dashed var(--line-strong);
            border-radius: 4px;
        }

        .doc-assinatura-linha {
            padding-top: 7px;
            border-top: 1px solid var(--ink);
        }

        .doc-assinatura strong {
            display: block;
            margin-bottom: 2px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .doc-assinatura span {
            display: block;
            color: var(--muted);
            font-size: 10px;
        }

        .doc-assinatura .doc-assinatura-data {
            margin-top: 6px;
            color: var(--ink);
        }

        /* ===== Rodapé ===== */
        .doc-footer {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-top: 22px;
            padding-top: 9px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 8px;
        }

        @media screen and (max-width: 720px) {
            body {
                padding: 12px;
            }

            .doc-page {
                min-height: 0;
                padding: 22px 16px;
            }

            .doc-header {
                grid-template-columns: 52px minmax(0, 1fr);
            }

            .doc-logo {
                width: 46px;
                height: 46px;
            }

            .doc-emission {
                grid-column: 1 / -1;
                text-align: left;
            }

            .doc-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .doc-heading h1 {
                font-size: 18px;
            }

            .doc-campos,
            .doc-assinaturas {
                grid-template-columns: 1fr;
            }

            .doc-campo.largo {
                grid-column: auto;
            }

            .doc-campos.em-linha .doc-campo {
                min-width: 100%;
            }

            .report-table-wrap table.rel,
            .doc-table-wrap table.rel {
                min-width: 720px;
            }

            .doc-footer {
                flex-direction: column;
                gap: 3px;
            }
        }

        @media print {
            @page {
                size: A4 {{ $retrato ? 'portrait' : 'landscape' }};
                margin: 8mm;
            }

            html,
            body {
                background: #ffffff !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .doc-page {
                width: 100%;
                max-width: none;
                min-height: 0;
                padding: 0;
                border: 0;
            }

            .doc-body,
            .report-table-wrap,
            .doc-table-wrap {
                overflow: visible;
            }

            table.rel thead {
                display: table-header-group;
            }

            table.rel tr,
            .doc-campo {
                break-inside: avoid;
            }

            table.rel tr.grupo {
                break-after: avoid;
            }

            table.rel tr.subtotal,
            table.rel tr.total {
                break-before: avoid;
            }

            table.rel tbody tr:not(.grupo):not(.subtotal):not(.total):hover td {
                background: inherit;
            }

            .doc-footer {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
<div class="doc-toolbar no-print">
    <button class="doc-print-button" type="button" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 9V2h12v7"></path>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect width="12" height="8" x="6" y="14"></rect>
        </svg>
        Imprimir
    </button>
</div>

<main class="doc-page">
    {{ $antes ?? '' }}

    <header class="doc-header">
        <img class="doc-logo" src="{{ $brasao }}" alt="Brasão do Tribunal de Justiça do Espírito Santo">

        <div class="doc-institution">
            <p class="doc-institution-name">Tribunal de Justiça do Estado do Espírito Santo</p>
            <p class="doc-institution-unit">{{ $secao }}</p>
        </div>

        <div class="doc-emission">
            <span class="doc-meta-label">Emitido em</span>
            <span class="doc-meta-value">{{ $emissao }}</span>
        </div>
    </header>

    <section class="doc-heading" aria-labelledby="doc-title">
        <div>
            @if ($kicker)
                <span class="doc-kicker">{{ $kicker }}</span>
            @endif
            <h1 id="doc-title">{{ $titulo }}</h1>
        </div>

        @if ($identificador)
            <div class="doc-badge">{{ $identificador }}</div>
        @endif
    </section>

    <div class="doc-body">
        {{ $slot }}
    </div>

    <footer class="doc-footer">
        <span>{{ $rodape }}</span>
        <span>{{ $rodapeDireita }}</span>
    </footer>
</main>

@if ($imprimirAoCarregar)
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
@endif
</body>
</html>
