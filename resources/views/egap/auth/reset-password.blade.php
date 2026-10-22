<x-filament-panels::page.simple>
    @component('egap.layouts.auth-shell', [
        'title' => 'Crie uma nova senha',
        'text' => 'Defina a senha que você passará a usar para entrar no EGAP.',
        'asideTitle' => 'Uma boa senha',
        'asideItems' => [
            [
                'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" />',
                'name' => 'Longa e só sua',
                'desc' => 'Prefira frases ou combinações longas. Não reutilize senhas de outros sistemas.',
            ],
            [
                'icon' => '<circle cx="12" cy="8" r="4" /><path d="M4 21v-1a6 6 0 0 1 12 0v1" />',
                'name' => 'Sem dados pessoais',
                'desc' => 'Evite CPF, datas de nascimento, matrícula ou o nome do setor.',
            ],
            [
                'icon' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><path d="m1 1 22 22" />',
                'name' => 'Não compartilhe',
                'desc' => 'A Administração nunca pede sua senha. Cada acesso fica registrado no seu nome.',
            ],
        ],
        'help' => 'Depois de salvar, você será levado ao login para entrar com a nova senha.',
    ])
        {{ $this->content }}

        @slot('actions')
            @if (filament()->hasLogin())
                <a class="nf__link" href="{{ filament()->getLoginUrl() }}">Voltar ao login</a>
            @endif
        @endslot
    @endcomponent
</x-filament-panels::page.simple>
