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
                        <h4 class="my-4 font-bold text-md">SOLICITAÇÃO DE EMPENHO</h4>
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
                                <td class="w-[25%] font-bold border-2">UNIDADE GESTORA</td>
                                <td class="w-[25%] border-2">(   ) Fundo (030901)   ( x ) TJ (03101)</td>
                            </tr>
                            <tr>
                                <td class="w-[25%] font-bold border-2">No. DA NOTA DE RESERVA</td>
                                <td class="w-[25%] border-2">{{ $notareserva }}</td>
                            </tr>
                            <tr>
                                <td class="w-[25%] font-bold border-2">No. DO SEI</td>
                                <td class="w-[25%] border-2">{{ $processo }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 class="font-bold text-md">1. CLASSIFICAÇÂO</h4>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="font-bold border-2 w-[50%]">Programa de Trabalho</td>
                                    <td class="font-bold border-2">4020</td>
                                </tr>
                                <tr>
                                    <td class="font-bold border-2 w-[50%]">Natureza da despesa</td>
                                    <td class="font-bold border-2">{{ $natureza }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold border-2 w-[50%]">Razão Social ou Nome</td>
                                    <td class="font-bold border-2">{{ $nome }}</td>
                                </tr>
                            </tdoby>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 class="font-bold text-md">2. DETALHAMENTO</h4>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="font-bold border-2 w-[25%]">Tipo de licitação<br/>No. ______</td>
                                    <td class="font-bold border-2 w-[25%]">(  ) Convite<br/>( x ) Inexigível <br/>(  ) Pregão</td>
                                    <td class="font-bold border-2 w-[25%]">(  ) Tomada de Preço<br/>(  ) Dispensa Licitação <br/>(  ) Licitação Deserta</td>
                                    <td class="font-bold border-2 w-[25%]">(  ) Concorrência<br/>(  ) Não aplicável <br/>(  ) Outros ______</td>
                                </tr>
                                <tr>
                                    <td class="font-bold border-2 w-[25%]">Há contrato?</td>
                                    <td class="font-bold border-2 w-[25%]">( x ) Não<br/>(  ) Sim. No. ______</td>
                                </tr>
                            </tdoby>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 class="font-bold text-md">3. ITENS</h4>
                        <table class="w-full mb-3 border-2">
                            <tdoby>
                                <tr>
                                    <td class="font-bold border-2">NATUREZA</td>
                                    <td class="font-bold border-2">DESCRIÇÃO SUCINTA DO OBJETO (SERVIÇO/BEM)</td>
                                    <td class="font-bold border-2">VALOR TOTAL</td>
                                </tr>
                                @foreach ($eventos as $evento)
                                <tr>
                                    <td class="h-8 border-2 w-[25%]">{{ $evento->orcamento->natureza->codigo }}</td>
                                    <td class="h-8 border-2 w-[50%]">{{ $evento->orcamento->descricao }}</td>
                                    <td class="h-8 border-2 w-[25%]">R$ {{ number_format($evento->valor,2,'.',',') }}</td>
                                </tr>
                                @endforeach
                            </tdoby>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4>Notas:</h4>
                        <div class="mx-4">
                            <ul class="list-decimal">
                                <li>O subitem da despesa deve ser extraído da Nota de Reserva e informar se é da 1ª ou da 2ª instância.</li>
                                <li>Cabe à Seção de Empenho e Classificação de Despesa da Secretaria de Finanças e Execução Orçamentária - nas normas de Procedimentos da NP 01 que couber - efetuar o seguinte procedimento:
                                    <ul class="ml-4 list-disc">
                                        <li>Validar as informações prestadas pela Secretaria Competente no Projeto Básico/Termo de Referência, referente à Classificação Orçamentária e à indicação do Elemento de Despesa.</li>
                                        <li>Caso à classificação orçamentária e/ou a indicação do Elemento de Despesa informado pela Secretaria Competente sejam divergentes do que for estabelecido pela legislação específica, os autos deverão retornar à Secretaria requerente para a devida correção do Projeto Básico/Termo de Referência.</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
