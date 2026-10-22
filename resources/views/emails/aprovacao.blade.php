<div style="font-family: Arial, sans-serif; background: #f9fafb; padding: 32px;">
    <p style="text-align: center;"><img src="https://www.tjes.jus.br/emes/images/logo.png"></p>
    <div
        style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #e5e7eb; padding: 32px;">
        <h2 style="color: #16a34a; margin-bottom: 16px;">Você foi aprovado!</h2>
        <p style="font-size: 16px; color: #374151;">
            Você foi <strong>aprovado</strong> no curso <strong>{!! $conteudo['titulo'] !!}</strong>!
        </p>
        <p style="font-size: 15px; color: #6b7280; margin-top: 24px;">
            Para disponibilização do certificado preencha o <a href="{{ $formUrl }}"
                style="color: #2563eb;">Formulario
                de avaliação</a>.
        </p>
        <p style="font-size: 15px; color: #6b7280; margin-top: 24px;">
            Em caso de dúvidas, entre em contato com {{ config('settings.email.emes') }}.
        </p>
        <hr style="margin: 32px 0;">
        <p style="font-size: 13px; color: #9ca3af;">
            Este é um e-mail automático, por favor não responda.
        </p>
    </div>
</div>
