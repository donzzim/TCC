<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Administrativo\ResidenciaJuridicaResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\Administrativo\ResidenciaJuridica;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\CursoCertificados;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ResidenciaJuridicaResource extends Resource
{
    protected static ?string $model = ResidenciaJuridica::class;
    protected static ?string $modelLabel = 'Residência JurÃ­dica ';
    protected static ?string $puralModelLabel = 'Residência JurÃ­dica ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Residência JurÃ­dica ';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 6;
    public static function getFormfields(): array
    {
        return [
            Section::make('Residência JurÃ­dica')
                ->schema([
                    Forms\Components\Select::make('pessoa_id')
                        ->relationship(name: 'pessoa', titleAttribute: 'name')
                        ->searchable(['name'])
                        ->label('Pessoa')
                        ->preload()
                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(4),

                    Forms\Components\RichEditor::make('observacao')
                        ->label('Observação')
                        ->label('Observação')
                        ->columnSpan(4),
                ])
                ->columnSpan(4)
                ->columns(4),

            Section::make('Período')
                ->schema([
                    Forms\Components\DatePicker::make('inicio')
                        ->label('Data início')
                        ->required()
                        ->live(debounce: 800)
                        // ->default(date('Y-m-d'))
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            $inicio = Carbon::parse($state)->addMonths(3);
                            $termino = Carbon::parse($state)->addYears(3)->subDays(1);
                            $period = CarbonPeriod::create($inicio, 'P3M', $termino);
                            $data = $inicio;
                            $json = [];
                            foreach ($period as $data) {
                                $json[] = [
                                    'data_prazo' => $data->format('Y-m-d'),
                                    'data_email' => null,
                                    'situacao' => 'Aguardando',
                                ];
                            }
                            $set('termino', $termino->format('Y-m-d'));
                            $set('json', $json);
                        })
                        ->columnSpan(1),

                    Forms\Components\DatePicker::make('termino')
                        ->label('Data término')
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\DatePicker::make('data_desligamento')
                        ->label('Data Desligamento')
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('municipio')
                        ->label('Município')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('processo')
                        ->label('Núm. Processo')
                        ->mask('99999-99.9999.9.99.9999')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('carga_horaria')
                        ->label('Carga Horária')
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('curso')
                        ->label('Fez Curso?')
                        ->inline(false)
                        ->columnSpan(1),
                ])
                ->columnSpan(2)
                ->columns(3),

            Forms\Components\Repeater::make('json')
                ->defaultItems(0)
                ->label('Envio de Notificação')
                ->schema([
                    Forms\Components\DatePicker::make('data_prazo')
                        ->label('Prazo')
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('data_email')
                        ->label('Email')
                        ->columnSpan(2),

                    Forms\Components\Select::make('situacao')
                        ->label('Situação')
                        ->options([
                            'Aguardando' => 'Aguardando',
                            'Enviado' => 'Enviado',
                            'Pendente' => 'Pendente',
                            'Informado' => 'Informado',
                            'Registrado' => 'Registrado',
                        ])
                        ->default('Aguardando')
                        ->columnSpan(2),
                ])
                ->itemLabel(function (array $state) {
                    if (!$state['data_prazo'])
                        return 'Sem prazo';

                    $title = '';
                    if ($state['data_prazo']) {
                        $dt = Carbon::parse($state['data_prazo'])->format('d/m/Y');
                        $title .= "Prazo: {$dt}";
                    }
                    if ($state['data_email']) {
                        $dt = Carbon::parse($state['data_email'])->format('d/m/Y');
                        $title .= " Email: {$dt}";
                    }
                    if ($state['situacao']) {
                        $title .= " Situação: {$state['situacao']}";
                    }
                    return $title;
                })
                ->collapsed()
                ->columnSpanFull()
                ->columns(6),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(6);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pessoa.name')
                    ->label('Pessoa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('processo')
                    ->label('Núm. Processo')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('inicio')
                    ->dateTime('d/m/Y')
                    ->label('Início')
                    ->sortable(),

                Tables\Columns\TextColumn::make('termino')
                    ->dateTime('d/m/Y')
                    ->label('Término')
                    ->sortable(),

                Tables\Columns\TextColumn::make('municipio')
                    ->label('Município')
                    ->sortable(),

                Tables\Columns\TextColumn::make('carga_horaria')
                    ->label('Carga Horária')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('curso')
                    ->label('Fez Curso?')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('json')
                    ->label('Prazos')
                    ->sortable()
                    ->wrap()
                    ->html()
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        if (!$record->json)
                            return null;

                        $output = '';
                        $i = 1;
                        $seq = 1;
                        foreach ($record->json as $item) {
                            $html = '';
                            if ($item['data_prazo'])
                                $html = 'Prazo: ' . Carbon::parse($item['data_prazo'])->format('d/m/Y');
                            if ($item['data_email'])
                                $html = 'Email: ' . Carbon::parse($item['data_email'])->format('d/m/Y H:i:s');
                            if ($item['situacao'])
                                $html = 'Situação: ' . $item['situacao'];
                                $html = 'Situação: ' . $item['situacao'];

                            // cor cinza - data_prazo preenchido e data_email e situação não preenchido
                            if ($item['data_prazo'] && !$item['data_email'] && $item['situacao'] == 'Aguardando') {
                                $dt = Carbon::parse($item['data_prazo'])->format('d/m/Y');
                                $output .= '<button data-tooltip-target="tooltip-default' . $seq . '" type="button" class="bg-slate-100 text-slate-800 text-xs font-medium me-1 px-1 py-0.5 rounded-md border border-slate-200 w-24">' . $dt . '</button>
                                <div id="tooltip-default' . $seq . '" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                ' . $html . '
                                <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>';

                                // cor amarelo - data_prazo e data_email preenchidos e situacao='Enviado'
                            } else if ($item['data_prazo'] && $item['data_email'] && $item['situacao'] == 'Enviado') {
                                $dt = Carbon::parse($item['data_prazo'])->format('d/m/Y');
                                $output .= '<button data-tooltip-target="tooltip-default' . $seq . '" type="button" class="bg-yellow-100 text-yellow-800 text-xs font-medium me-1 px-1 py-0.5 rounded-md border border-yellow-200 w-24">' . $dt . '</button>
                                <div id="tooltip-default' . $seq . '" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                    ' . $html . '
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>';

                                // cor vermelho - data_prazo e data_email preenchidos e situacao='Pendente'
                            } else if ($item['data_prazo'] && $item['data_email'] && $item['situacao'] == 'Pendente') {
                                $dt = Carbon::parse($item['data_prazo'])->format('d/m/Y');
                                $output .= '<button data-tooltip-target="tooltip-default' . $seq . '" type="button" class="bg-red-100 text-red-800 text-xs font-medium me-1 px-1 py-0.5 rounded-md border border-red-200 w-24">' . $dt . '</button>
                                <div id="tooltip-default' . $seq . '" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                    ' . $html . '
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>';
                                // cor verde - data_prazo e data_email preenchidos e situacao='Informado'
                            } else if ($item['data_prazo'] && $item['data_email'] && $item['situacao'] == 'Informado') {
                                $dt = Carbon::parse($item['data_prazo'])->format('d/m/Y');
                                $output .= '<button data-tooltip-target="tooltip-default' . $seq . '" type="button" class="bg-green-100 text-green-800 text-xs font-medium me-1 px-1 py-0.5 rounded-md border border-green-200 w-24">' . $dt . '</button>
                                <div id="tooltip-default' . $seq . '" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                    ' . $html . '
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>';
                            }
                            if ($i == 3) {
                                $output .= "<br/>";
                                $i = 1;
                            } else {
                                $i++;
                            }
                            $seq++;
                        }
                        return $output;
                    }),

                Tables\Columns\ToggleColumn::make('situacao')
                    ->label('Situação')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('situação')
                    ->toggle()
                    ->default()
                    ->query(fn(Builder $query): Builder => $query->where('situacao', 1)),

                SelectFilter::make('Pessoa')
                    ->relationship(
                        name: 'pessoa',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->preload()
                    ->columnSpan(8),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(8)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),
                Action::make('gerar_certificado')
                    ->hiddenLabel()
                    ->hidden(fn(Model $record) => $record->certificado()->count() > 0)
                    ->tooltip('Gerar Certificado')
                    ->icon('heroicon-o-academic-cap')
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $modelo_id = 5;

                        $modelo = CertificadoModelos::find($modelo_id);

                        $record->certificado()->create([
                            'certificable_id' => $record->id,
                            'certificable_type' => $record::class,
                            'pessoa_id' => $record->pessoa_id,
                            'modelo_id' => $modelo_id,
                            'assinado_por' => $modelo->assinado_por,
                            'atualizado_por' => Auth::user()->id,
                            'disponivel' => 1,
                        ]);
                    }),
                Action::make('imprimir')
                    ->hiddenLabel()
                    ->hidden(fn(Model $record) => $record->certificado()->count() < 0)
                    ->tooltip('Imprimir certificado')
                    ->icon('heroicon-o-printer')
                    ->url(fn(Model $record): string => route('imprimir.certificado.residente', $record->uuid))
                    ->openUrlInNewTab(),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                // ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidenciaJuridicas::route('/'),
            'create' => Pages\CreateResidenciaJuridica::route('/create'),
            'edit' => Pages\EditResidenciaJuridica::route('/{record}/edit'),
        ];
    }
}
