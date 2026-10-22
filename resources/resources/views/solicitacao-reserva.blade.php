<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>EMES</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js" integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        @vite([ 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="p-4 font-sans">

        <table class="w-full table-auto">
            <thead>
                <tr>
                    <td class="w-full text-center">
                        <h4 class="grid justify-items-center"><img class="w-20 h-20" src="{{ asset('storage/brasao_es.png') }}" /></h4>
                        <h4 class="text-xs font-semibold">PODER JUDICIÁRIO DO ESTADO DO ESPÍRITO SANTO - PJES</h4>
                        <h4 class="text-xs font-semibold">SECRETARIA DE INFRAESTRUTURA</h4>
                        <h4 class="text-xs font-semibold">SISTEMA DE COMPRAS, LICITAÇÕES, CONTRATOS E INSTRUMENTOS CONGÊNERES</h4>
                        <h4 class="my-4 font-bold text-md">SOLICITAÇÃO DE RESERVA</h4>
                    </td>
                </tr>
                <tr>
                    <td class="flex">
                        <div class="flex-1 text-left">Solicitação de Reserva Nº <b>{{ $numero }}</b> - ESCOLA DA MAGISTRATURA - EMES</div>
                        <div class="flex-1 text-right">Em {{ date('d/m/ Y', strtotime($data)) }}</div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <table class="w-full my-3 border-2">
                            <tr>
                                <td colspan="4" class="border-2">Reforço da Nota de Reserva? (se sim, informar nº): (INCLUIR REFERENCIA DO Nº SEI)</td>
                            </tr>
                            <tr>
                                <td class="w-[25%] font-bold border-2">UNIDADE GESTORA</td>
                                <td class="w-[25%] border-2">03101</td>
                                <td class="w-[25%] font-bold border-2">PROGRAMA DE TRABALHO</td>
                                <td class="w-[25%] border-2">4020</td>
                            </tr>
                            <tr>
                                <td class="w-[25%] font-bold border-2">MICRO-REGIÃO</td>
                                <td class="w-[25%] border-2">00</td>
                                <td class="w-[25%] font-bold border-2">ÁREA</td>
                                <td class="w-[25%] border-2">EMES</td>
                            </tr>
                            <tr>
                                <td class="w-[25%] font-bold border-2">PLANO ORÇAMENTÁRIO</td>
                                <td class="w-[25%] border-2">1473</td>
                                <td class="w-[25%] border-2"></td>
                                <td class="w-[25%] border-2"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="font-bold border-2 w-[50%]">NATUREZA DE DESPESA</td>
                                    <td class="font-bold border-2">VALOR</td>
                                </tr>
                                @foreach ($eventos as $evento)
                                <tr>
                                    <td class="border-2">{{ $evento->orcamento->natureza->codigo }}: {{ $evento->orcamento->descricao }}</td>
                                    <td class="border-2">R$ {{ number_format($evento->valor,2,'.',',') }}</td>
                                </tr>
                                @endforeach
                            </tdoby>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="border-2">PROJETO PREVISTO NO ORÇAMENTO (Especificar):</td>
                                </tr>
                                <tr>
                                    <td class="border-2">NOVO PROJETO A SER REALIZADO (Especificar):</td>
                                </tr>
                                <tr>
                                    <td class="border-2">INSUFICIENCIA DE SALDO? / ORIGEM DO RECURSO?</td>
                                </tr>
                            </tdoby>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="text-center">INDICAÇÃO DE PROJETO QUE NÃO SERÁ REALIZADO OU SUPRIMIDO E VALOR A SER REMANEJADO</td>
                </tr>
                <tr>
                    <td>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="border-2">PROJETO</td>
                                    <td class="border-2">Destino</td>
                                    <td class="border-2">Plano<br/>Orçamentário</td>
                                    <td class="border-2">NATUREZA</td>
                                    <td class="border-2">SUBITEM</td>
                                    <td class="border-2">MICRO-REGIÃO</td>
                                    <td class="border-2">VALOR</td>
                                </tr>
                                <tr>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                </tr>
                                <tr>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                </tr>
                                <tr>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                    <td class="h-8 border-2"></td>
                                </tr>
                            </tdoby>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
