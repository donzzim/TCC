<?php

namespace App\Filament\Resources\Contratacao\ContratacaoCursoResource\RelationManagers;

use App\Filament\Resources\Contratacao\ContratacaoCursoResource;
use App\Filament\Resources\Contratacao\ProcessoResource;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Escola\Cursos;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProcessosCursosRelationManager extends RelationManager
{
    protected static string $relationship = 'processosCursos';

    protected static ?string $title = 'Instrutores e Processos Vinculados';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('instrutor_id')
                    ->label('Instrutor')
                    ->relationship('instrutor', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('processo_id')
                    ->label('Processo')
                    ->relationship('processo', 'numero')
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('instrutor.name')
                    ->label('Instrutor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('processo.numero')
                    ->label('Nº Processo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('processo.tipo.descricao')
                    ->label('Tipo de Contratação')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('Vincular Novo'),

                Action::make('cadastrar_novo_processo')
                    ->label('Cadastrar Novo Processo')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->modalHeading('Registrar Novo Processo de Contratação')
                    ->form(fn (): array => ProcessoResource::getFormfields())
                    ->fillForm(fn (self $livewire): array => [
                        'cursos' => [[
                            'curso_id' => $livewire->getOwnerRecord()->id,
                        ]],
                    ])
                    ->action(function (array $data): void {
                        $curso = $this->getOwnerRecord();

                        DB::transaction(function () use ($curso, $data): void {
                            ContratacaoCursoResource::createProcessoComVinculos($curso, $data);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Processo criado e vinculado com sucesso!')
                            ->success()
                            ->send();
                    }),

                Action::make('cadastrar_lote_processos')
                    ->label('Cadastrar em Lote')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('warning')
                    ->modalHeading('Registrar Processos de Contratação em Lote')
                    ->modalWidth('5xl')
                    ->form(fn (): array => [
                        Forms\Components\Repeater::make('processos_lote')
                            ->label('Processos')
                            ->addActionLabel('Adicionar outro Processo')
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['numero'] ?? 'Novo Processo')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('numero')
                                    ->label('Número do Processo')
                                    ->required(),
                                Forms\Components\Select::make('tipoprocesso_id')
                                    ->label('Tipo de Processo')
                                    ->options(fn (): array => \App\Models\Contratacao\TipoProcesso::query()->pluck('descricao', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('modalidade_id')
                                    ->label('Modalidade da Contratação')
                                    ->options(fn (): array => \App\Models\Contratacao\ModalidadeContratacao::query()->pluck('descricao', 'id')->all())
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Radio::make('fisica_juridica')
                                    ->label('Pessoa')
                                    ->options(['F' => 'Física', 'J' => 'Jurídica'])
                                    ->inline(),
                                Forms\Components\Select::make('curso_id')
                                    ->label('Curso')
                                    ->options(fn (): array => Cursos::query()->pluck('titulo', 'id')->all())
                                    ->default($this->getOwnerRecord()->id)
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('instrutor_id')
                                    ->label('Instrutor')
                                    ->options(fn (): array => \App\Models\User::query()->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $curso = $this->getOwnerRecord();

                        DB::transaction(function () use ($curso, $data): void {
                            foreach ($data['processos_lote'] as $item) {
                                $processo = Processo::create([
                                    'numero' => $item['numero'],
                                    'tipoprocesso_id' => $item['tipoprocesso_id'] ?? null,
                                    'modalidade_id' => $item['modalidade_id'] ?? null,
                                    'fisica_juridica' => $item['fisica_juridica'] ?? null,
                                    'descricao' => $item['descricao'] ?? null,
                                    'ativo' => true,
                                    'setor_id' => $curso->setor_id,
                                ]);

                                ProcessoCursos::create([
                                    'curso_id' => $item['curso_id'],
                                    'instrutor_id' => $item['instrutor_id'] ?? null,
                                    'processo_id' => $processo->id,
                                ]);
                            }
                        });

                        \Filament\Notifications\Notification::make()
                            ->title(count($data['processos_lote']) . ' processos criados e vinculados com sucesso!')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('visualizar_eventos')
                    ->label('Ver Eventos')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (ProcessoCursos $record): string => 'Extrato de Eventos Financeiros - Processo: ' . ($record->processo?->numero ?? 'N/A'))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->infolist([
                        RepeatableEntry::make('processo.processo_eventos')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('tipoevento.descricao')
                                    ->label('Tipo de Evento')
                                    ->badge(),
                                TextEntry::make('numero_documento')
                                    ->label('Nº Documento/Nota')
                                    ->placeholder('N/A'),
                                TextEntry::make('orcamento.descricao')
                                    ->label('Orçamento')
                                    ->limit(30),
                                TextEntry::make('data_nota')
                                    ->label('Data')
                                    ->date('d/m/Y')
                                    ->placeholder('N/A'),
                                TextEntry::make('valor')
                                    ->label('Valor (R$)')
                                    ->money('BRL'),
                            ])
                            ->columns(5),
                    ]),

                Action::make('gerenciar_eventos')
                    ->label('Eventos')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn (ProcessoCursos $record): string => ProcessoResource::getUrl('edit', ['record' => $record->processo_id]))
                    ->openUrlInNewTab(),
            ]);
    }
}
