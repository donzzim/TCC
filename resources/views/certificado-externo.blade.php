<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado - EMES</title>
    <style>
        html,
        body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
        }

        iframe {
            display: block;
            width: 100vw;
            height: 100vh;
            border: none;
        }

        /* =========================================
           CONFIGURAÇÕES GERAIS (Reset)
           ========================================= */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14pt;
        }

        /* Configuração da Página A4 Paisagem */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        /* Classe que define uma folha de papel */
        .sheet {
            position: relative;
            width: 297mm;
            /* Largura A4 Paisagem */
            height: 210mm;
            /* Altura A4 Paisagem */
            overflow: hidden;
            page-break-after: always;
            /* Força nova página após cada folha */
            break-after: page;
        }


        /* =========================================
           ESTILOS PARA IMPRESSÃO (PDF/Papel)
           ========================================= */
        @media print {
            body {
                background-color: white;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
                width: 1150px;
                height: 800px;
            }

            .bg-img {
                position: absolute;
                top: 5px;
                left: 5px;
                height: 100%;
                width: 100%;
                z-index: 0;
                object-fit: cover;
            }

            .conteudo-frente {
                position: relative;
                z-index: 10;
                /* Ajuste o padding para posicionar o texto onde a arte permite */
                padding-top: 9cm;
                /* Ajuste conforme sua imagem */
                padding-left: 2cm;
                padding-right: 2cm;
                text-align: center;
                /* Geralmente certificados são centralizados */
            }
        }

        /* =========================================
           ELEMENTOS DA PÁGINA 2 (VERSO)
           ========================================= */
        .conteudo-verso {
            padding: 2cm;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        table.tabela-verso {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            flex-grow: 1;
            /* Ocupa o espaço disponível */
        }

        table.tabela-verso td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }

        .titulo-secao {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            padding: 8px;
        }

        .texto-conteudo {
            font-size: 12pt;
            text-align: justify;
            min-height: 300px;
            /* Garante altura mínima para o conteúdo */
        }

        .rodape-validacao {
            font-size: 10pt;
            vertical-align: middle !important;
        }
    </style>
</head>

<body>

    @if (isset($arquivo))
        {{-- <img src="{{ $arquivo }}" class="bg-img" alt="Fundo"> --}}
        <iframe src="{{ $arquivo }}" class="bg-img" alt="Fundo" width="100%" height="100%"
            style="border: none;"></iframe>
    @else
        <p style="text-align: center; margin-top: 50px;">Nenhum arquivo de certificado externo encontrado.</p>
    @endif
</body>

</html>
