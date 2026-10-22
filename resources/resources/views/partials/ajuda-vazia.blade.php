<div class="text-center p-4">
    <div class="mb-4 text-gray-500">
        Nenhuma documentação específica encontrada para esta tela.
    </div>

    @if(auth()->user()->hasRole('super_admin'))
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm border border-gray-200 dark:border-gray-700">
            <p class="font-bold text-primary-600 mb-2">Informação para Administrador:</p>
            <p>Para criar uma ajuda para esta tela, vá em <strong>Documentações</strong> e crie um registro com a rota:</p>
            <code class="block mt-2 p-2 bg-white dark:bg-gray-900 rounded select-all cursor-pointer text-primary-500">
                {{ $rota }}
            </code>
        </div>
    @endif
</div>
