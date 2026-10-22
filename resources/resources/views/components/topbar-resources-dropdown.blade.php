{{-- resources/views/components/topbar-resources-dropdown.blade.php --}}
<div class="flex items-center mr-2 gap-x-2"> {{-- Container para os múltiplos dropdowns --}}
    @php
        $groupedResources = [];
        // Itera sobre todos os painéis registrados no Filament
        foreach (\Filament\Facades\Filament::getPanels() as $panel) {
            // Filtra para o seu painel principal (ex: 'admin').
            // ATENÇÃO: SUBSTITUA 'admin' PELO ID REAL DO SEU PAINEL Filament.
            if ($panel->getId() === 'admin') {
                // Itera sobre todos os Resources registrados para este painel
                foreach ($panel->getResources() as $resourceClassName) {
                    // Verifica se o usuário tem permissão para visualizar o resource
                    if ($resourceClassName::canViewAny()) {
                        // Obtém o grupo de navegação do Resource.
                        // Se não houver grupo definido ($navigationGroup), ele será agrupado como 'Outros'.
                        $group = $resourceClassName::getNavigationGroup() ?? 'Outros';

                        // Coleta os dados necessários de cada Resource
                        $groupedResources[$group][] = [
                            'icon' => $resourceClassName::getNavigationIcon(),
                            'label' => $resourceClassName::getModelLabel(), // Seu label singular
                            'pluralLabel' => $resourceClassName::getPluralLabel(),
                            'route' => $resourceClassName::getUrl('index'),
                        ];
                    }
                }
            }
        }

        // Opcional: Ordene os grupos alfabeticamente para uma exibição consistente
        ksort($groupedResources);
    @endphp

    @if (empty($groupedResources))
        {{-- Caso não haja recursos disponíveis para o usuário --}}
        <x-filament::button disabled>
            Nenhum recurso disponível.
        </x-filament::button>
    @else
        {{-- Loop pelos grupos de recursos para criar um dropdown para cada grupo --}}
        @foreach ($groupedResources as $groupName => $resourcesInGroup)
            <x-filament::dropdown placement="bottom-end">
                <x-slot:trigger>
                    {{-- O botão que aciona o dropdown para este grupo --}}
                    <x-filament::link
                        {{-- icon="heroicon-o-squares-2x2" Use um ícone adequado ou remova se preferir apenas texto --}}
                        label="{{ $groupName }}" {{-- Label para acessibilidade/tooltip --}}
                        tag="a"
                        href="#"
                        class="fi-topbar-nav-item"
                    >
                        {{ $groupName }} {{-- Texto visível no botão do grupo --}}
                    </x-filament::link>
                </x-slot:trigger>

                <x-filament::dropdown.list>
                    {{-- Loop pelos recursos dentro do grupo atual --}}
                    @foreach ($resourcesInGroup as $resourceData)
                        <x-filament::dropdown.list.item
                            :icon="$resourceData['icon']" {{-- Usa o ícone do Resource --}}
                            :href="$resourceData['route']"
                            tag="a" {{-- Link para a rota do Resource --}}
                        >
                            {{ $resourceData['label'] }} {{-- Usa o label singular do Resource --}}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @endforeach
    @endif
</div>
