<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite([ 'resources/css/app.css', 'resources/css/principal.css','resources/js/app.js'])
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            .no-print {
                display: none;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</head>

<body>
    <header>
        @include('reports.report-cabecalho')
    </header>

    <main>
        <div class="meta-info" style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
            <h2 class="text-xl">@yield('report_name')</h2>
            @yield('filters_summary')
        </div>

        @yield('content')
    </main>

    <footer>
        @include('reports.report-rodape')
    </footer>
</body>

</html>
