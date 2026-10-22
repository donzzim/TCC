<x-filament-panels::page.simple>
    @component('egap.layouts.auth-shell', [
        'title' => 'Esqueceu sua senha?',
        'text' => 'Informe o e-mail do seu cadastro e enviaremos um link para você criar uma nova senha.',
        'asideTitle' => 'Como funciona',
        'asideItems' => [
            [
                'icon' => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" />',
                'name' => 'Link no seu e-mail',
                'desc' => 'Você recebe um link único para definir a nova senha. A senha atual continua valendo até lá.',
            ],
            [
                'icon' => '<circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />',
                'name' => 'Validade limitada',
                'desc' => 'O link expira em pouco tempo. Se vencer, basta pedir outro por esta mesma tela.',
            ],
            [
                'icon' => '<path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />',
                'name' => 'Não chegou?',
                'desc' => 'Confira a caixa de spam e lixo eletrônico antes de solicitar um novo envio.',
            ],
        ],
        'help' => 'Por segurança, a tela não informa se o e-mail está cadastrado. Sem acesso ao e-mail, procure a Administração do EGAP.',
    ])
        {{ $this->content }}

        @slot('actions')
            @if (filament()->hasLogin())
                <a class="nf__link" href="{{ filament()->getLoginUrl() }}">Voltar ao login</a>
            @endif
        @endslot
    @endcomponent
</x-filament-panels::page.simple>
