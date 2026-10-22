<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado - EMES</title>
    <style>
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
            width: 297mm;  /* Largura A4 Paisagem */
            height: 210mm; /* Altura A4 Paisagem */
            overflow: hidden;
            page-break-after: always; /* Força nova página após cada folha */
            break-after: page;
        }

        /* =========================================
           ESTILOS PARA TELA (Visualização Browser)
           ========================================= */
        @media screen {
            body {
                background-color: #e0e0e0; /* Fundo cinza para destacar o papel */
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
            }

            .sheet {
                background-color: white;
                margin-bottom: 20px; /* Espaço entre frente e verso na tela */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); /* Sombra para efeito de papel */
            }

            .bg-img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 0;
                object-fit: cover;
            }

            .conteudo-frente {
                position: relative;
                z-index: 10;
                /* Ajuste o padding para posicionar o texto onde a arte permite */
                padding-top: 9cm;  /* Ajuste conforme sua imagem */
                padding-left: 2cm;
                padding-right: 2cm;
                text-align: center; /* Geralmente certificados são centralizados */
            }
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
                padding-top: 9cm;  /* Ajuste conforme sua imagem */
                padding-left: 2cm;
                padding-right: 2cm;
                text-align: center; /* Geralmente certificados são centralizados */
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
            flex-grow: 1; /* Ocupa o espaço disponível */
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
            min-height: 300px; /* Garante altura mínima para o conteúdo */
        }

        .rodape-validacao {
            font-size: 10pt;
            vertical-align: middle !important;
        }
    </style>
</head>

<body>
    @if (isset($conteudo))

    <div class="sheet">
        <img src="{{ $imagem }}" class="bg-img" alt="Fundo">

        <div class="conteudo-frente">
            {!! $texto !!}
        </div>
    </div>

    <div class="sheet">
        <div class="conteudo-verso">
            <table class="tabela-verso">
                <thead>
                    <tr>
                        <td colspan="2" class="titulo-secao">
                            Informações Complementares
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2" style="height: 70%;">
                            <strong>Conteúdo Programático:</strong>
                            <div class="texto-conteudo">
                                {!! $conteudo ?? '' !!}
                            </div>
                        </td>
                    </tr>

                    <tr style="height: 1px;"> <td style="width: 120px; text-align: center; vertical-align: middle;">
                            <img src="{{ $qrcode }}" alt="QR Code" style="width: 100px; height: 100px;">
                        </td>
                        <td class="rodape-validacao">
                            <p>
                                A autenticidade deste documento pode ser verificada no sistema
                                <strong>{{ config('app.name') }}</strong>.<br>
                                Endereço eletrônico: <a href="https://sistemas.tjes.jus.br/emes" style="color: #000; text-decoration: none;">https://sistemas.tjes.jus.br/emes</a>
                            </p>
                            <p>
                                Chave de validação: <strong>{{ $chave }}</strong>
                            </p>
                            <p>
                                Ou através da leitura do QR Code ao lado.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @else
        <div style="text-align: center; padding: 50px;">
            <p>Certificado indisponível.</p>
        </div>
    @endif
</body>

</html>
