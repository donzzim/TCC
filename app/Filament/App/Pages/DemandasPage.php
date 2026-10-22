<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\InteractsWithAgendaValidations;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\TipoDemandas;
use App\Models\Escola\TipoLocal;
use App\Services\AgendaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * @property Schema $form
 */
class DemandasPage extends Page implements HasForms, HasTable
{
    use InteractsWithAgendaValidations;
    use InteractsWithForms;
    use InteractsWithTable;

    // protected static string $resource = DemandasResource::class;

    protected string $view = 'filament.app.resources.demandas-resource.pages.demandas';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Demandas';

    protected static ?string $modelLabel = 'Demandas';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return filter_var(
            config('settings.demandas.ativo', false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function getFormfields(): array
    {
        return [
            TextInput::make('titulo')
                ->label('Tí­tulo')
                ->required()
                ->maxLength(255)
                ->lazy()
                ->columnSpan(3),
            RichEditor::make('descricao')
                ->label('Descrição')
                ->required()
                ->lazy()
                ->columnSpan(4),
            Select::make('tipodemanda_id')
                ->label('Tipo Demanda')
                ->options(TipoDemandas::all()->pluck('descricao', 'id'))
                ->required()
                ->searchable()
                ->columnSpan(3)
                ->lazy(),
            Select::make('local_id')
                ->label('Local')
                ->live()
                ->required()
                ->options(TipoLocal::all()->pluck('descricao', 'id'))
                ->hidden(function (Get $get) {
                    return $get('tipodemanda_id') != config('settings.tipodemanda.agendamento');
                })
                ->required()
                ->searchable()
                ->columnSpan(3)
                ->lazy(),
            DateTimePicker::make('data_prevista')
                ->label('Data Inicio')
                ->live()
                ->required()
                ->columnSpan(3)
                ->lazy()
                ->hidden(function (Get $get) {
                    if ($get('local_id')) {
                        return false;
                    }

                    return true;
                }),

            DateTimePicker::make('data_termino')
                ->label('Data Termino')
                ->required()
                ->live()
                ->columnSpan(3)
                ->lazy()
                ->hidden(function (Get $get) {
                    if ($get('local_id')) {
                        return false;
                    }

                    return true;
                }),

        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormfields())
            ->columns(6)
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipodemanda.descricao')
                    ->label('Tipo Demanda'),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Tí­tulo')
                    ->wrap()
                    ->description(function (Demanda $record) {
                        $instrutor = $record->nome_instrutor ? "Instrutor: {$record->nome_instrutor}" : 'Sem instrutor';
                        $data = $record->data_prevista ? "Data prevista: {$record->data_prevista}" : 'Sem data prevista';

                        return new HtmlString("{$instrutor} , {$data}");
                    })
                    ->searchable(),

                Tables\Columns\ImageColumn::make('indicado.avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/admin-tie.png'))
                    ->circular(),

                Tables\Columns\TextColumn::make('indicado.name')
                    ->label('Solicitado por')
                    ->formatStateUsing(function (string $state) {
                        $words = explode(' ', $state);

                        return reset($words) . ' ' . end($words);
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('tags.descricao')
                    ->label('Tags')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('Criado em')
                    ->sortable(),
                Tables\Columns\TextColumn::make('card.descricao')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->color(function (Demanda $record) {

                        return Color::hex($record->card->cor ?? '#64748B');
                    })
                    ->badge(),
                Tables\Columns\ToggleColumn::make('ativo'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nova Demanda')
                    ->modalHeading('Nova Demanda')
                    ->schema(static::getFormfields())
                    ->mutateFormDataUsing(function ($action, array $data, Get $get) {

                        if (AgendaService::verificaConflito($data['local_id'], $data['data_termino'], $data['data_prevista'])) {
                            logger()->info('Conflito detectado na agenda!');
                            $action->halt();
                        }

                        $data['indicado_por'] = Filament::auth()->id();

                        return $data;
                    })
                    ->after(function ($record, $data) {
                        if (! empty($data['local_id'])) {
                            $record->agendas()->create([
                                'titulo' => 'Demanda',
                                'descricao' => $data['descricao'] ?? '',
                                'inicio' => $data['data_prevista'], // ajuste conforme necessário
                                'termino' => $data['data_termino'], // ajuste conforme necessário
                                'local_id' => $data['local_id'],
                                'atualizado_por' => Filament::auth()->id(),
                            ]);
                        }
                    })
                    ->slideOver()
                    ->successNotificationTitle('Demanda criada com sucesso!'),
            ])
            ->filters([
                Tables\Filters\Filter::make('ativo')
                    ->toggle()
                    ->default()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1)),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable(false)
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading()
            ->query(Demanda::query()->where('indicado_por', Filament::auth()->id()));
    }
}
