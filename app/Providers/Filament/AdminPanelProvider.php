<?php

namespace App\Providers\Filament;

// use Filament\Schemas\Components\Grid;
use App\Filament\Pages\Auth\LoginAdmin;
use App\Http\Middleware\LogRequestTiming;
use App\Livewire\BotaoAjudaGlobal;
use App\Livewire\CustomProfileComponent;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
// use Filament\Notifications\Notification;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
// use Filament\Actions\Action;
// use Filament\Forms\Components\CheckboxList;
// use Filament\Forms\Components\Repeater;
// use Filament\Forms\Components\Select;
// use Filament\Forms\Components\TextInput;
// use Filament\Tables\Enums\FiltersLayout;
// use Filament\Tables\Table;
// use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\View\WidgetsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
// use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;
// use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Jeffgreco13\FilamentBreezy\Livewire\SanctumTokens;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use rbdutra\DynamicForms\Resources\FormResource;
use rbdutra\DynamicForms\Resources\SubmissionResource;
use ReflectionClass;

class AdminPanelProvider extends PanelProvider
{

    public function menuTopBar()
    {
        $portal = '<x-filament::link tag="a" size="md" color="gray" href="https://www.tjes.jus.br/emes" icon="heroicon-o-home" target="_blank">Portal</x-filament:link>';
        $ead = '<x-filament::link tag="a" size="md" color="info" href="https://sistemas.tjes.jus.br/ead/login/index.php" icon="heroicon-o-academic-cap" target="_blank">EaD</x-filament:link>';
        $externa = '<x-filament::link tag="a" size="md" color="success" href="' . env('APP_URL') . '/emes/app" icon="heroicon-o-users" target="_blank">Área Externa</x-filament:link>';
        $ajuda = \Livewire\Livewire::mount(BotaoAjudaGlobal::class);
        $select = '@livewire(\'setor-switcher\')';

        return $portal . $ead . $externa . $select . $ajuda;
    }

    public function boot()
    {

        /*
        Table::configureUsing(function (Table $table) {

            $livewire = $table->getLivewire();
            if (! $livewire) return;

            $filterSessionKey = 'adv_filter_' . md5(get_class($livewire));
            $filterRules = Session::get($filterSessionKey, []);

            // Lógica para montar a lista de colunas (Agora com Múltiplos Campos por Relação)
            $getModelColumns = function () use ($table) {
                $model = $table->getModel();
                if (!$model) return [];

                $instance = new $model;
                // Colunas do Model Principal
                $fillables = $instance->getFillable();
                $options = [];

                // 1. Adiciona colunas nativas do model principal
                $options['id'] = 'ID';
                $options['created_at'] = 'Criado em';
                $options['updated_at'] = 'Atualizado em';

                foreach ($fillables as $column) {
                    // Adiciona a coluna nativa primeiro (ex: valor, status, nome)
                    // Se for chave estrangeira (ex: user_id), pulamos a exibição bruta
                    // para dar preferência aos campos da relação, mas se quiser manter ambos, remova o 'continue'.
                    if (Str::endsWith($column, '_id')) {
                        // Tenta processar o relacionamento
                        $relationName = Str::camel(Str::replaceLast('_id', '', $column));

                        // Caso especial: atualizado_por -> atualizado
                        if ($column === 'atualizado_por') $relationName = 'atualizado';

                        if (method_exists($instance, $relationName)) {
                            try {
                                $relatedQuery = $instance->$relationName();
                                // Verifica se é uma relação válida do Eloquent
                                if ($relatedQuery instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                                    $relatedModel = $relatedQuery->getRelated();
                                    $relatedFillables = $relatedModel->getFillable();

                                    // Adiciona ID do relacionado
                                    $options["{$relationName}.id"] = Str::headline($relationName) . " (ID)";

                                    // Adiciona TODAS as colunas fillable do relacionado
                                    foreach ($relatedFillables as $relatedCol) {
                                        // Evita senhas e tokens
                                        if (in_array($relatedCol, ['password', 'remember_token', 'two_factor_secret'])) continue;

                                        $label = Str::headline($relationName) . " (" . Str::headline($relatedCol) . ")";
                                        $options["{$relationName}.{$relatedCol}"] = $label;
                                    }

                                    // Não adiciona o campo _id original para não poluir
                                    continue;
                                }
                            } catch (\Exception $e) {
                                // Se der erro na reflexão da relação, adiciona o campo _id normal abaixo
                            }
                        }
                    }

                    // Adiciona coluna normal se não foi processada como relação
                    $options[$column] = Str::headline($column);
                }

                return $options;
            };

            $livewire = $table->getLivewire();
            if (! $livewire) return;

            $filterSessionKey = 'adv_filter_' . md5(get_class($livewire));
            $filterRules = Session::get($filterSessionKey, []);

            // --- LÓGICA DE AGRUPAMENTO ---
            $getGroupedColumns = function () use ($table) {
                $model = $table->getModel();
                if (!$model) return [];

                $instance = new $model;
                $fillables = $instance->getFillable();

                // Inicializa o grupo Principal
                $groups = [
                    'Principal' => [
                        'id' => 'ID',
                        'created_at' => 'Criado em',
                        'updated_at' => 'Atualizado em',
                        'atualizado_por' => 'Atualizado por',
                    ]
                ];

                foreach ($fillables as $column) {
                    // Verifica Relacionamentos (terminados em _id)
                    if (Str::endsWith($column, '_id')) {
                        $relationName = Str::camel(Str::replaceLast('_id', '', $column));
                        if ($column === 'atualizado_por') $relationName = 'atualizado';

                        if (method_exists($instance, $relationName)) {
                            try {
                                $relatedQuery = $instance->$relationName();
                                if ($relatedQuery instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                                    $relatedModel = $relatedQuery->getRelated();
                                    $relatedFillables = $relatedModel->getFillable();

                                    // Nome amigável do Grupo (ex: Aluno, Curso)
                                    $groupName = Str::headline($relationName);

                                    // Inicializa array do grupo se não existir
                                    if (!isset($groups[$groupName])) {
                                        $groups[$groupName] = [];
                                    }

                                    // Adiciona ID
                                    $groups[$groupName]["{$relationName}.id"] = "ID";

                                    // Adiciona colunas do relacionado
                                    foreach ($relatedFillables as $relatedCol) {
                                        if (in_array($relatedCol, ['password', 'remember_token', 'two_factor_secret'])) continue;

                                        // Label mais limpo (apenas o nome do campo, pois o grupo já diz o contexto)
                                        $label = Str::headline($relatedCol);
                                        $groups[$groupName]["{$relationName}.{$relatedCol}"] = $label;
                                    }
                                    continue;
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }

                    // Se não for relação, entra no Principal
                    $groups['Principal'][$column] = Str::headline($column);
                }

                return $groups;
            };

            return $table
                // Injeta Actions (Filtro e Exportar)
                ->headerActions([

                    // --- AÇÃO: FILTRO AVANÇADO ---
                    Action::make('advanced_filter_global')
                        ->label('Filtro Avançado')
                        ->extraAttributes(['class' => 'hidden']) // Oculto, acionado pelo botão customizado
                        ->modalWidth('7xl')
                        ->modalHeading('Filtro Dinâmico')
                        ->fillForm(['rules' => $filterRules])
                        ->form([
                            Repeater::make('rules')
                                ->label('')
                                ->schema(function () use ($getModelColumns) {
                                    return [
                                        Grid::make(4)->schema([
                                            Select::make('logic')
                                                ->options(['and' => 'E (AND)', 'or' => 'OU (OR)'])
                                                ->default('and')
                                                ->selectablePlaceholder(false),
                                            Select::make('column')
                                                ->options($getModelColumns())
                                                ->searchable()
                                                ->required(),
                                            Select::make('condition')
                                                ->options([
                                                    'equals' => 'Igual',
                                                    'contains' => 'Contém',
                                                    'greater' => 'Maior que',
                                                    'less' => 'Menor que',
                                                    'is_null' => 'Vazio',
                                                    'is_not_null' => 'Preenchido',
                                                ])
                                                ->default('contains')
                                                ->required(),
                                            TextInput::make('value')->label('Valor')
                                        ]),
                                    ];
                                })->addActionLabel('Adicionar Regra')->defaultItems(0)
                        ])
                        ->action(function (array $data) use ($filterSessionKey, $table) {
                            Session::put($filterSessionKey, $data['rules']);
                            $table->getLivewire()->dispatch('$refresh');
                        })
                        ->extraModalFooterActions([
                            Action::make('clear_filter')->label('Limpar')->color('danger')
                                ->action(function () use ($filterSessionKey, $table) {
                                    Session::forget($filterSessionKey);
                                    $table->getLivewire()->dispatch('$refresh');
                                })->cancelParentActions()
                        ]),

                    // --- EXPORTAR DINÂMICO AGRUPADO ---
                    Action::make('dynamic_export')
                        ->label('Exportar Excel')
                        ->modalWidth('7xl')
                        ->modalHeading('Exportar Registros')
                        ->modalDescription('Selecione os campos por contexto.')
                        ->extraAttributes(['class' => 'hidden'])
                        ->form(function () use ($getGroupedColumns) {
                            $groups = $getGroupedColumns();
                            $schema = [];

                            foreach ($groups as $groupName => $options) {
                                // Ordena opções dentro do grupo
                                asort($options);

                                // Gera uma Section para cada grupo (Principal, Aluno, Curso...)
                                // Usamos um nome de campo único baseado no grupo (ex: cols_principal, cols_aluno)
                                $fieldId = 'cols_' . Str::slug($groupName);

                                $schema[] = \Filament\Schemas\Components\Section::make($groupName)
                                    ->schema([
                                        CheckboxList::make($fieldId)
                                            ->hiddenLabel() // O título da Section já serve de label
                                            ->options($options)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->bulkToggleable() // Permite selecionar "Todos do Aluno"
                                            // Seleciona tudo por padrão apenas no Principal
                                            ->default($groupName === 'Principal' ? array_keys($options) : [])
                                    ])
                                    ->collapsible()
                                    ->compact();
                            }
                            return $schema;
                        })
                        ->action(function (array $data, Table $table) {
                            $livewire = $table->getLivewire();
                            $query = $livewire->getFilteredTableQuery();

                            // 1. Consolida os dados dos múltiplos CheckboxLists
                            $selectedColumns = [];
                            foreach ($data as $key => $values) {
                                if (Str::startsWith($key, 'cols_') && is_array($values)) {
                                    $selectedColumns = array_merge($selectedColumns, $values);
                                }
                            }

                            if (empty($selectedColumns)) $selectedColumns = ['id'];

                            // 2. Identifica Relações (Eager Loading)
                            $relationsToLoad = [];
                            foreach ($selectedColumns as $col) {
                                if (Str::contains($col, '.')) {
                                    $parts = explode('.', $col);
                                    $relationsToLoad[] = $parts[0];
                                }
                            }
                            if (!empty($relationsToLoad)) {
                                $query->with(array_unique($relationsToLoad));
                            }

                            // 3. Geração do Excel
                            $spreadsheet = new Spreadsheet();
                            $sheet = $spreadsheet->getActiveSheet();

                            // Header
                            $colIndex = 1;
                            foreach ($selectedColumns as $colKey) {
                                $label = Str::title(str_replace(['.', '_'], ' ', $colKey));
                                $sheet->setCellValueByColumnAndRow($colIndex++, 1, $label);
                                $sheet->getStyleByColumnAndRow($colIndex - 1, 1)->getFont()->setBold(true);
                            }

                            // Body
                            $rowIndex = 2;
                            $query->chunk(500, function ($rows) use ($sheet, $selectedColumns, &$rowIndex) {
                                foreach ($rows as $row) {
                                    $colIndex = 1;
                                    foreach ($selectedColumns as $colKey) {
                                        $val = null;
                                        if (Str::contains($colKey, '.')) {
                                            [$relation, $field] = explode('.', $colKey);
                                            $val = $row->{$relation}?->{$field};
                                        } else {
                                            $val = $row->{$colKey};
                                        }

                                        if ($val instanceof \Carbon\Carbon) {
                                            $val = $val->format('d/m/Y H:i');
                                        } elseif (is_bool($val)) {
                                            $val = $val ? 'Sim' : 'Não';
                                        } elseif (is_array($val) || is_object($val)) {
                                            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                        }

                                        $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $val);
                                    }
                                    $rowIndex++;
                                }
                            });

                            foreach (range(1, count($selectedColumns)) as $colID) {
                                $sheet->getColumnDimensionByColumn($colID)->setAutoSize(true);
                            }

                            $fileName = 'export_' . date('Ymd_His') . '.xlsx';
                            $writer = new Xlsx($spreadsheet);
                            $path = 'app/private/' . $fileName;
                            if (!is_dir(storage_path('app/private'))) mkdir(storage_path('app/private'), 0755, true);
                            $writer->save(storage_path($path));

                            $downloadUrl = url("/exports/download/{$fileName}");

                            $recipient = Auth::user();
                            if ($recipient) {
                                Notification::make()
                                    ->title('Exportação Concluída')
                                    ->body("Arquivo XLSX gerado.")
                                    ->success()
                                    ->persistent()
                                    ->recordActions([
                                        Action::make('download')
                                            ->label('Baixar XLSX')
                                            ->url($downloadUrl)
                                            ->button()
                                            ->openUrlInNewTab(),
                                    ])
                                    ->sendToDatabase($recipient);

                                Notification::make()
                                    ->title('Download Pronto')
                                    ->success()
                                    ->recordActions([
                                        Action::make('download')
                                            ->label('Baixar')
                                            ->url($downloadUrl)
                                            ->button()
                                            ->openUrlInNewTab(),
                                    ])->send();
                            }
                        }),
                ])

                // Query do Filtro
                ->modifyQueryUsing(function (Builder $query) use ($filterSessionKey) {
                    $rules = Session::get($filterSessionKey, []);
                    if (empty($rules)) return $query;
                    return $query->where(function (Builder $rootQuery) use ($rules) {
                        foreach ($rules as $index => $rule) {
                            $column = $rule['column'] ?? null;
                            $cond = $rule['condition'] ?? 'contains';
                            $val = $rule['value'] ?? null;
                            if (!$column) continue;
                            $method = ($index === 0 || ($rule['logic'] ?? 'and') === 'and') ? 'where' : 'orWhere';

                            if ($cond === 'contains') $rootQuery->{$method}($column, 'like', "%{$val}%");
                            elseif ($cond === 'equals') $rootQuery->{$method}($column, '=', $val);
                            elseif ($cond === 'greater') $rootQuery->{$method}($column, '>', $val);
                            elseif ($cond === 'less') $rootQuery->{$method}($column, '<', $val);
                            elseif ($cond === 'is_null') $rootQuery->{$method . 'Null'}($column);
                        }
                    });
                })
                ->filtersFormWidth('2xl')
                ->filtersTriggerAction(fn(Action $action) => $action->button()->label('Filtrar'))
                ->deferFilters()
                ->selectable()
                ->filtersLayout(FiltersLayout::Modal)
                ->striped();
        });
        */
    }


    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login(LoginAdmin::class)
            ->passwordReset()
            ->globalSearch(false)
            ->emailVerification()
            ->profile(isSimple: false)
            ->colors([
                'primary' => '#1d4970',
            ])
            // ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Planejamento Estratégico')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-estrategico'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Planejamento Orçamentário')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-orcamento'])
                    ->collapsed(),

                // EMES (EducaEMES)
                NavigationGroup::make()
                    ->label('Administrativo')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-administrativo'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Contratação')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-contratacao'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Projeto Pedagógico')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-pedagogico'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Cadastro')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-cadastro'])
                    ->collapsed(),

                // CSCP (e-GAP)
                NavigationGroup::make()
                    ->label('Almoxarifado')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-almoxarifado'])
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Patrimônio')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-patrimonio'])
                    ->collapsed(),

                // Designação de Magistrados
                NavigationGroup::make()
                    ->label('Presidência')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-presidencia'])
                    ->collapsed(),

                // Cartórios Extrajudiciais
                NavigationGroup::make()
                    ->label('Corregedoria')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-corregedoria'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Portal')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-portal'])
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Controle de Acesso')
                    ->extraSidebarAttributes(['class' => 'sidebar-group-padrao'])
                    // ->extraSidebarAttributes(['class' => 'sidebar-group-acesso'])
                    ->collapsed(),
            ])
            ->brandName('Educa Emes')
            // ->brandLogo(asset('images/logo.png'))
            // ->brandLogoHeight('50px')
            ->favicon(asset('images/favicon.ico'))
            // ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth('full')
            ->simplePageMaxContentWidth('sm')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchDebounce('750ms')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->pages([
            ])
            ->widgets([
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                LogRequestTiming::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetFilamentRouteContext::class,
                \App\Http\Middleware\AdminDebugbarMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureTjesProfileCompleted::class,
            ])
            ->renderHook(
                panelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn(): string => Blade::render($this->menuTopBar()),
            )
            // ->renderHook(
            //     panelsRenderHook::PAGE_START,
            //     fn(): string => \Livewire\Livewire::mount(BotaoAjudaGlobal::class),

            // )


            ->renderHook(
                'head.end',
                fn(): HtmlString => new HtmlString(<<<'CSS'
            <style>
            .filament-tables,
            .filament-tables-wrapper,
            .filament-tables-table,
            .filament-table {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .filament-tables table,
            .filament-tables-table table,
            .filament-table table {
                width: max-content;
                min-width: 100%;
            }

            .filament-tables .filament-table-actions,
            .filament-tables .filament-table-actions td,
            .filament-tables .filament-table-actions th {
                white-space: nowrap;
            }
            </style>
        CSS)
            )

            /*
            ->renderHook(
                TablesRenderHook::TOOLBAR_SEARCH_AFTER,
                fn(): string => Blade::render(<<<'BLADE'
                    <div class="flex items-center gap-2 ml-2">
                        <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-1 bg-white dark:bg-gray-800">
                            <x-filament::icon-button
                                icon="heroicon-o-funnel"
                                color="primary"
                                tooltip="Filtro Avançado"
                                wire:click="mountTableAction('advanced_filter_global')"
                            />
                        </div>

                        <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-1 bg-white dark:bg-gray-800">
                            <x-filament::icon-button
                                icon="heroicon-o-arrow-down-tray"
                                color="success"
                                tooltip="Exportar Excel"
                                wire:click="mountTableAction('dynamic_export')"
                            />
                        </div>
                    </div>
                BLADE)
            )
            */
            ->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        userMenuLabel: 'Meu perfil', // Customizes the 'account' link label in the panel User Menu (default = null)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        navigationGroup: 'Controle de Acesso', // Sets the navigation group for the My Profile page (default = null)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'perfil' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // force the user to enable 2FA before they can use the application (default = false)
                        // action: CustomTwoFactorPage::class, // optionally, use a custom 2FA page
                        // authMiddleware: MustTwoFactor::class, // optionally, customize 2FA auth middleware or disable it to register manually by setting false
                    )
                    ->enableSanctumTokens(
                        permissions: [
                            'my',
                            'custom',
                            'permissions',
                        ],
                    )
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                        'custom_profile' => CustomProfileComponent::class,
                        'update_password' => UpdatePassword::class,
                        'two_factor_authentication' => TwoFactorAuthentication::class,
                        'sanctum_tokens' => SanctumTokens::class,

                    ])
                    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('public')),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->spa();

        // $panelHooks = new ReflectionClass(PanelsRenderHook::class);
        // $tableHooks = new ReflectionClass(TablesRenderHook::class);
        // $widgetHooks = new ReflectionClass(WidgetsRenderHook::class);

        // $panelHooks = $panelHooks->getConstants();
        // $tableHooks = $tableHooks->getConstants();
        // $widgetHooks = $widgetHooks->getConstants();

        // foreach ($panelHooks as $hook) {
        //     $panel->renderHook($hook, function () use ($hook) {
        //         return Blade::render('<div style="border: solid red 1px; padding: 2px;">{{ $name }}</div>', [
        //             'name' => Str::of($hook)->remove('tables::'),
        //         ]);
        //     });
        // }
        // foreach ($tableHooks as $hook) {
        //     $panel->renderHook($hook, function () use ($hook) {
        //         return Blade::render('<div style="border: solid red 1px; padding: 2px;">{{ $name }}</div>', [
        //             'name' => Str::of($hook)->remove('tables::'),
        //         ]);
        //     });
        // }
        // foreach ($widgetHooks as $hook) {
        //     $panel->renderHook($hook, function () use ($hook) {
        //         return Blade::render('<div style="border: solid red 1px; padding: 2px;">{{ $name }}</div>', [
        //             'name' => Str::of($hook)->remove('tables::'),
        //         ]);
        //     });
        // }

        return $panel;
    }
}
