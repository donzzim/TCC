<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EMES</title>
    <style>
        @page {
            size: A4;
            margin-top: 0.5cm;
            margin-bottom: 0;
            margin-left: 0;
            margin-right: 0;
            padding: 0;
        }

        body {
            font-family: helvetica !important;
            font-size: 14pt;
            position: relative;
        }

        #overlay {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-image: url('{{ $imagem }}');
            background-position: left top;
            background-repeat: no-repeat;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            z-index: -1;
        }

        #content {
            position: relative;
            z-index: 99;
            height: 410px;
            width: 1030px;
            padding: 3.5cm 0.50cm 5.00cm 0.50cm;
        }

        #certificado {
            position: absolute;
            display: block;
            margin-top: 150px;
            margin-left: 70px;
            height: 400px;
            width: 900px;
        }

        #complemeto {
            position: absolute;
            display: flex;
            margin-top: 650px;
            margin-left: 20px;
            height: 600px;
            width: 1000px;
        }

        table {
            border-spacing: 0px;
        }

        td {
            padding: 5px;
        }
    </style>
</head>

<body>
    <div id="page-body">
        <div id="overlay"></div>
        <div id="content">
            <div id="certificado">{!! $texto !!}</div>
            <div id="complemeto">
                <table style="width: 100%; height: 100%; border: 2px solid #000000;">
                    <tr>
                        <td style="width: 100px; height: 50px; text-align: center; " colspan="2">Informações
                            Complementares</td>
                    </tr>

                    <tr>
                        <td style="width: 100px; height: 60px;"><img src="{{ $qrcode }}" alt="QR Code"
                                style="width: 100px; height: 100px;"></td>
                        <td colspan="2" style="font-size: 14px;">A autenticidade deste documento pode ser
                            verificada no
                            sistema {{ config('app.name') }}, disponível sob o endereço
                            eletrônico: https://sistemas.tjes.jus.br/emes , através da chave de validação:
                            {{ $chave }}
                            ou através
                            da leitura do QR Code, à esquerda.
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
