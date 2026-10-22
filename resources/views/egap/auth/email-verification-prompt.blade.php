<x-filament-panels::page.simple>
    @component('egap.layouts.auth-shell', [
        'title' => 'Confirme seu e-mail',
        'text' => 'Falta pouco: confirme o endereço de e-mail do seu cadastro para liberar o acesso ao painel.',
        'asideTitle' => 'Próximos passos',
        'asideItems' => [
            [
                'icon' => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" />',
                'name' => 'Abra o e-mail enviado',
                'desc' => 'Clique no link de confirmação. Ele abre o EGAP já com o acesso liberado.',
            ],
            [
                'icon' => '<path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />',
                'name' => 'Confira o spam',
                'desc' => 'Mensagens automáticas às vezes caem na caixa de spam ou lixo eletrônico.',
            ],
            [
                'icon' => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8" /><path d="M21 3v5h-5" />',
                'name' => 'Peça um novo envio',
                'desc' => 'Se o e-mail não chegar em alguns minutos, use o link de reenvio nesta tela.',
            ],
        ],
        'help' => 'E-mail do cadastro errado? Peça a correção à Administração do EGAP.',
    ])
        {{ $this->content }}
    @endcomponent
</x-filament-panels::page.simple>
