<div style="font-family: Arial, sans-serif; background: #f9fafb; padding: 32px;">
    <p style="text-align: center;"><img src="https://www.tjes.jus.br/emes/images/logo.png"></p>
    <div
        style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #e5e7eb; padding: 32px;">
        <h2 style="color: #2563eb; margin-bottom: 16px;">Seus certificados estão disponíveis!</h2>
        <p style="font-size: 16px; color: #374151;">
            Olá <strong>{!! $conteudo['aluno'] !!}</strong>, informamos que os certificados para o curso
            <strong>{!! $conteudo['titulo'] !!}</strong> já estão
            <strong>disponíveis</strong> para visualização e download no portal.
        </p>
        <p style="font-size: 15px; color: #6b7280; margin-top: 24px;">
            Acesse a plataforma e baixe seus certificados diretamente pelo seu perfil.
            <a href="{{ url('app/meu-certificados') }}" style="color: #2563eb;">Acesse seus certificados</a>
            Em caso de dúvidas, entre em contato com {{ config('settings.email.emes') }}.
        </p>

        <hr style="margin: 32px 0;">
        <p style="font-size: 13px; color: #9ca3af;">
            Este é um e-mail automático, por favor não responda.
        </p>
    </div>
</div>
