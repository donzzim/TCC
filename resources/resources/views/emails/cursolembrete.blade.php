<div style="font-family: Arial, sans-serif; background-color:#f4f6f9; padding:40px;">
    <table align="center" width="600" cellpadding="0" cellspacing="0"
        style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);">

        <!-- Header com Logo -->
        <tr>
            <td style="text-align:center; padding:32px 20px;">
                <img src="https://www.tjes.jus.br/emes/images/logo.png" alt="Logo EMES" style="max-width:180px;">
            </td>
        </tr>

        <tr>
            <td style="padding:0 40px 40px 40px; color:#374151;">

                <!-- Título -->
                <h2 style="font-size:22px; color:#2563eb; margin:0 0 15px 0; font-weight:700;">
                    Lembrete de Inscrição!
                </h2>

                <!-- Saudação -->
                <p style="font-size:16px; line-height:1.5;">
                    Olá <strong>{!! $conteudo['aluno'] !!}</strong>,<br>
                    o curso <strong>{!! $conteudo['titulo'] !!}</strong> irá ocorrer em
                    @if ($conteudo['diffDays'] > 1)
                        <strong>{!! $conteudo['diffDays'] !!} dias</strong>
                    @else
                        <strong> 1 dia </strong>
                    @endif
                </p>

                <!-- Detalhes -->
                <div
                    style="background:#f8fafc; border-left:4px solid #2563eb; margin-top:25px; padding:18px 20px; border-radius:4px;">
                    <p style="margin:0; font-size:15px; color:#475569;"><strong>Detalhes do Curso:</strong></p>

                    @if ($conteudo['atividades'] && $conteudo['atividades']->count() > 0)
                        <ul style="padding-left:18px; margin-top:10px; font-size:14px; color:#555;">
                            @foreach ($conteudo['atividades'] as $atividade)
                                <li style="margin-bottom:12px;">
                                    <strong>{{ $atividade->titulo }}</strong><br>
                                    📅 <strong>Início:</strong> {{ $atividade->inicio }}<br>
                                    📅 <strong>Término:</strong> {{ $atividade->termino }}<br>
                                    @if ($atividade->tipoLocal)
                                        📍 <strong>Local:</strong>
                                        {{ $atividade->tipoLocal->descricao ?? 'Não informado' }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="margin-top:10px; font-size:14px;">
                            📅 <strong>Início:</strong> {{ $conteudo['inicio'] }}<br>
                            📅 <strong>Término:</strong> {{ $conteudo['termino'] }}<br>
                            📍 <strong>Local:</strong> {{ $conteudo['local'] }}
                        </p>
                    @endif
                </div>

                @if ($conteudo['link_reuniao'])
                    <div style="margin-top:28px; padding:15px 18px; background:#eef6ff; border-radius:6px;">
                        <p style="margin:0; font-size:15px; color:#1e3a8a;"><strong>🖥 Acesso ao Evento Online:</strong>
                        </p>
                        <a href="{{ $conteudo['link_reuniao'] }}"
                            style="display:inline-block; margin-top:6px; font-size:14px; color:#2563eb; font-weight:bold; text-decoration:none;">
                            <span style="word-break:break-all;">{{ $conteudo['link_reuniao'] }}</span>
                        </a>
                        <p style="margin-top:8px; font-size:14px; color:#1e3a8a;">
                            🔐 <strong>Senha:</strong> {{ $conteudo['senha_reuniao'] }}
                        </p>
                    </div>
                @endif

                <!-- Rodapé / Ação -->
                {{-- <p style="font-size:14px; margin-top:30px; color:#475569;">
                    Em caso de dúvidas, contate: <strong>{{ config('settings.email.emes') }}</strong><br>
                </p> --}}


                <hr style="margin:32px 0; border:0; border-top:1px solid #e2e8f0;">

                <p style="text-align:center; font-size:12px; color:#94a3b8;">
                    Este é um e-mail automático, por favor não responda.<br>
                    {{ date('Y') }} EMES
                </p>

            </td>
        </tr>
    </table>
</div>
