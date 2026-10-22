<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Cartaz com QR Code - {{ $curso->titulo ?? 'Curso' }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 297mm;
            height: 210mm;
            font-family: Arial, Helvetica, sans-serif;
            background: #2b2b2b;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 297mm;
            height: 210mm;
            display: flex;
            background: #2b2b2b;
            color: #ffffff;
            overflow: hidden;
        }

        .left,
        .right {
            width: 50%;
            height: 100%;
        }

        .left {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14mm 10mm 14mm 14mm;
        }

        .poster-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .poster {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .poster-fallback {
            width: 100%;
            height: 100%;
            background: #d9d9d9;
            color: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            font-size: 24px;
            font-weight: bold;
        }

        .right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* <-- centralização vertical real */
            padding: 18mm 18mm 18mm 22mm;
            text-align: center;
        }

        .title-top {
            font-size: 17px;
            letter-spacing: 0.8px;
            font-weight: 300;
            margin: 0;
            text-transform: uppercase;
            color: #f1f1f1;
        }

        .title-main {
            font-size: 38px;
            line-height: 1;
            font-weight: 800;
            margin: 6px 0 0 0;
            text-transform: uppercase;
            color: #ffffff;
        }

        .qr-box {
            margin-top: 24px;
            margin-bottom: 16px;
            background: #8a8a8a;
            padding: 5mm;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .qr-box img {
            width: 42mm;
            height: 42mm;
            display: block;
            background: #ffffff;
            padding: 2mm;
        }

        .subtitle {
            font-size: 16px;
            line-height: 1.35;
            color: #e2e2e2;
            max-width: 70%;
        }

        @media print {

            html,
            body {
                width: 297mm;
                height: 210mm;
            }

            .print-hidden {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    {{-- botão opcional --}}
    <button class="print-hidden" onclick="window.print()"
        style="position: fixed; top: 16px; right: 16px; z-index: 999; padding: 8px 12px;">
        Imprimir
    </button>

    <div class="page">

        <div class="left">
            <div class="poster-wrapper">
                @if ($cartaz)
                    <img src="{{ $cartaz }}" alt="Cartaz do curso" class="poster">
                @else
                    <div class="poster-fallback">
                        Cartaz do curso não disponível
                    </div>
                @endif
            </div>
        </div>

        <div class="right">
            <h1 class="title-top">Registro de</h1>
            <h2 class="title-main">Frequência</h2>

            <div class="qr-box">
                <img src="{{ $qrcode }}" alt="QR Code de frequência">
            </div>

            <div class="subtitle">
                Escaneie o QR Code para registrar a presença no curso.
            </div>
        </div>

    </div>

</body>

</html>
