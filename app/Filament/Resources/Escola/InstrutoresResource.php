<?php

namespace App\Filament\Resources\Escola;

use App\Filament\Exports\EscolaCursoInstrutoresExporter;
use App\Filament\Exports\SynchronousExportAction;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Escola\InstrutoresResource\Pages\CreateInstrutores;
use App\Filament\Resources\Escola\InstrutoresResource\Pages\EditInstrutores;
use App\Filament\Resources\Escola\InstrutoresResource\Pages\ListInstrutores;
use App\Models\Contratacao\ModalidadeContratacao;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Contratacao\ProcessoDocumento;
use App\Models\Escola\Instrutores;
use App\Models\Escola\TipoAtividade;
use App\Models\Escola\TipoEvento;
use App\Models\Escola\TipoRaca;
use App\Services\DocumentoContratacaoService;
use Exception;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use UnitEnum;

class InstrutoresResource extends Resource
{
    protected static ?string $model = Instrutores::class;
    protected static ?string $modelLabel = 'Instrutor';
    protected static ?string $puralModelLabel = 'Instrutores';
    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Instrutores';
    protected static ?int $navigationSort = 3;

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static UnitEnum|string|null $navigationGroup = 'Projeto Pedagógico';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'instrutor',
            'curso',
            'processosCursos.processo.modalidade'
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Como as edições ocorrem via Actions, o form padrão pode ficar vazio
                // ou ser utilizado para edições manuais extremas.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instrutor.name')
                    ->label('Instrutor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Instrutores $record): string => 'CPF: ' . ($record->instrutor->cpf ?? 'Não informado')),

                TextColumn::make('curso.titulo')
                    ->label('Curso / Evento')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('modalidade')
                    ->label('Modalidade')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn(Instrutores $record) => $record->processoCursoCapa->processo->modalidade->descricao ?? 'Não definida'),

                TextColumn::make('numero_sei')
                    ->label('Processo SEI')
                    ->placeholder('Não iniciado')
                    ->getStateUsing(fn(Instrutores $record) => $record->processoCursoCapa->processo->numero ?? null)
                    ->searchable(query: function (Builder $query, string $search) {
                        // Como a coluna é virtual, ensinamos o Filament a buscar no relacionamento real
                        $query->whereHas('processosCursos.processo', function ($q) use ($search) {
                            $q->where('numero', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('status_documentacao')
                    ->label('Status da Contratação')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pendente_admin' => 'gray',
                        'pendente_instrutor' => 'warning',
                        'em_analise' => 'info',
                        'aprovado' => 'success',
                        'devolvido' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pendente_admin' => 'Pendente Envio',
                        'pendente_instrutor' => 'Aguardando Instrutor',
                        'em_analise' => 'Em Análise (RH)',
                        'aprovado' => 'Aprovado',
                        'devolvido' => 'Devolvido p/ Correção',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_documentacao')
                    ->label('Status')
                    ->options([
                        'pendente_admin' => 'Pendente Envio',
                        'pendente_instrutor' => 'Aguardando Instrutor',
                        'em_analise' => 'Em Análise (RH)',
                        'aprovado' => 'Aprovado',
                    ]),
            ])
            ->actions([
                ActionGroup::make([

                    // =========================================================================
                    // ACTION 1: SOLICITAR DOCUMENTAÇÃO
                    // =========================================================================
                    Action::make('solicitar_documentos')
                        ->label('Solicitar Documentos')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->modalHeading('Iniciar Contratação e Solicitar Documentos')
                        ->modalDescription('Selecione a Modalidade de Contratação. Isso criará o Processo Administrativo e gerará os termos necessários baseados na modalidade escolhida.')
                        ->visible(fn(Instrutores $record) => in_array($record->status_documentacao, [null, 'pendente_admin', 'pendente_instrutor', 'devolvido']))
                        ->form([
                            Select::make('modalidade_id')
                                ->label('Modalidade de Contratação')
                                ->options(ModalidadeContratacao::all()->pluck('descricao', 'id'))
                                ->required()
                                ->searchable()
                        ])
                        ->action(function (Instrutores $record, array $data) {

                            try {
                                DB::beginTransaction();

                                // 1. Criar o Processo Principal (SEI) se não existir
                                $processoCurso = $record->processoCursoCapa;

                                if (!$processoCurso) {
                                    $processo = Processo::create([
                                        'numero' => Str::uuid(), // Pode substituir por sua lógica de numeração SEI se houver
                                        'setor_id' => $record->curso->setor_id ?? Auth::user()->setorPrincipal()->first()->id ?? null,
                                        'tipoprocesso_id' => $record->tipoprocesso_id,
                                        'modalidade_id' => $data['modalidade_id'],
                                        'descricao' => "Contratação de Instrutoria - {$record->instrutor->name} - {$record->curso->titulo}",
                                        'fisica_juridica' => 'F',
                                        'ativo' => true,
                                        'atualizado_por' => Auth::id(),
                                    ]);

                                    ProcessoCursos::create([
                                        'processo_id' => $processo->id,
                                        'curso_id' => $record->curso_id,
                                        'instrutor_id' => $record->instrutor_id,
                                    ]);
                                } else {
                                    // Atualiza a modalidade caso o processo já exista
                                    $processoCurso->processo->update(['modalidade_id' => $data['modalidade_id']]);

                                    // Limpa os documentos anteriores gerados que estavam pendentes, para não duplicar se mudar a modalidade
                                    ProcessoDocumento::where('instrutor_id', $record->id)
                                        ->where('status_aprovacao', '!=', 'aprovado')
                                        ->delete();
                                }

                                // 2. Gerar os templates dinâmicos enviando a Modalidade selecionada
                                if (class_exists(DocumentoContratacaoService::class)) {
                                    app(DocumentoContratacaoService::class)->gerarPendenciasParaInstrutor($record, $data['modalidade_id']);
                                }

                                // 3. Atualizar Status do Instrutor na Pivot
                                $record->update(['status_documentacao' => 'pendente_instrutor']);

                                // 4. Envia a notificação interna para o instrutor
                                Notification::make()
                                    ->title('Ação Necessária: Documentação de Contratação')
                                    ->body("Por favor, preencha os dados e assine os termos referentes ao curso: {$record->curso->titulo}.")
                                    ->actions([
                                        Action::make('Preencher Agora')
                                            ->url(route('filament.app.pages.dashboard'))
                                            ->button(),
                                    ])
                                    ->sendToDatabase($record->instrutor);

                                DB::commit(); // Se chegou até aqui, salva tudo no banco

                                Notification::make()->title('Solicitação enviada com sucesso!')->success()->send();
                            } catch (Exception $e) {
                                DB::rollBack(); // Se deu qualquer erro no service, cancela a criação do Processo e afins

                                \Illuminate\Support\Facades\Log::error('Erro ao Solicitar Documentos: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Erro ao processar solicitação')
                                    ->body('Ocorreu um erro interno. Verifique os logs do sistema. Detalhe: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // =========================================================================
                    // ACTION 2: ANALISAR DOCUMENTAÇÃO (SLIDE-OVER)
                    // =========================================================================
                    Action::make('analisar_documentacao')
                        ->label('Analisar Documentação')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('info')
                        ->slideOver()
                        ->visible(fn(Instrutores $record) => in_array($record->status_documentacao, [null, 'em_analise', 'aprovado']))
                        ->form(function (Instrutores $record) {

                            $schema = [];

                            // --- SEÇÃO 1: Dados do Instrutor e Processo ---
                            $schema[] = Section::make('Resumo do Instrutor e Processo')
                                ->columns(2)
                                ->schema([
                                    Placeholder::make('processo_sei')
                                        ->label('Processo SEI / Modalidade')
                                        ->content(new HtmlString(
                                            "
                                            <strong>Nº SEI:</strong> " . ($record->processoCursoCapa->processo->numero ?? 'Não cadastrado') . "<br>
                                            <strong>Modalidade:</strong> " . ($record->processoCursoCapa->processo->modalidade->descricao ?? 'Não definida')
                                        )),

                                    Placeholder::make('dados_cadastrais')
                                        ->label('Dados Pessoais/Perfil')
                                        ->content(new HtmlString("
                                            <strong>CPF:</strong> {$record->instrutor->cpf} <br>
                                            <strong>Telefone:</strong> {$record->instrutor->telefone} <br>
                                            <strong>Lattes:</strong> " . ($record->perfil->curriculo ?? 'N/A') . "
                                        ")),

                                    Placeholder::make('dados_bancarios')
                                        ->label('Dados Fiscais e Pagamento')
                                        ->columnSpanFull()
                                        ->content(new HtmlString("
                                            <strong>CNPJ:</strong> " . ($record->cnpj ?? 'N/A') . " <br>
                                            <strong>Banco:</strong> " . ($record->dados_bancarios['banco'] ?? 'N/A') . " <br>
                                            <strong>Ag/Conta:</strong> " . ($record->dados_bancarios['agencia'] ?? '-') . " / " . ($record->dados_bancarios['conta'] ?? '-') . "
                                        ")),
                                ]);

                            // --- SEÇÃO 2: Análise Documento a Documento (Gerado Dinamicamente) ---
                            $documentos = $record->documentosContratacao()->with(['modelo', 'arquivoPessoal'])->get();

                            $schemaDocs = [];
                            foreach ($documentos as $doc) {
                                $schemaDocs[] = Grid::make(1)->schema([
                                    Fieldset::make($doc->modelo->nome ?? 'Documento Avulso')
                                        ->columns(3)
                                        ->schema([
                                            Placeholder::make("link_doc_{$doc->id}")
                                                ->label('Visualização')
                                                ->content(function () use ($doc) {
                                                    if ($doc->esc_documento_id && $doc->arquivoPessoal && $doc->arquivoPessoal->arquivo) {
                                                        $url = \Illuminate\Support\Facades\Storage::url($doc->arquivoPessoal->arquivo);
                                                        return new HtmlString("<a href='{$url}' target='_blank' class='text-primary-600 font-bold underline'>⬇️ Visualizar Anexo</a>");
                                                    } elseif ($doc->is_assinado) {
                                                        $data = $doc->assinado_em ? $doc->assinado_em->format('d/m/Y H:i') : '';
                                                        return new HtmlString("<span class='text-green-600 font-semibold'>✅ Assinado Digitalmente em {$data}</span>");
                                                    }
                                                    return new HtmlString("<span class='text-yellow-600'>⚠️ Pendente de ação do instrutor</span>");
                                                })->columnSpan(1),

                                            Radio::make("status_doc_{$doc->id}")
                                                ->label('Parecer do RH')
                                                ->options([
                                                    'aprovado' => 'Aprovado',
                                                    'rejeitado' => 'Rejeitado',
                                                ])
                                                ->default($doc->status_aprovacao !== 'pendente' ? $doc->status_aprovacao : null)
                                                ->inline()
                                                ->reactive()
                                                ->required()
                                                ->columnSpan(1),

                                            Textarea::make("motivo_doc_{$doc->id}")
                                                ->label('Motivo para Ajustes (Rejeição)')
                                                ->default($doc->motivo_rejeicao)
                                                ->rows(2)
                                                ->visible(fn(Get $get) => $get("status_doc_{$doc->id}") === 'rejeitado')
                                                ->required(fn(Get $get) => $get("status_doc_{$doc->id}") === 'rejeitado')
                                                ->columnSpan(1),
                                        ])
                                ]);
                            }

                            if (count($schemaDocs) > 0) {
                                $schema[] = Section::make('Análise de Documentos e Certidões')->schema($schemaDocs);
                            } else {
                                $schema[] = Section::make('Análise de Documentos')->schema([
                                    Placeholder::make('empty')->hiddenLabel()->content('Nenhum documento gerado para esta contratação ainda.')
                                ]);
                            }

                            // --- SEÇÃO 3: Decisão Final da Contratação ---
                            $schema[] = Section::make('Conclusão da Análise')
                                ->schema([
                                    Radio::make('decisao_final')
                                        ->label('Resultado Global da Contratação')
                                        ->options([
                                            'aprovado' => 'Aprovar Contratação (Todos os documentos estão corretos)',
                                            'devolvido' => 'Devolver Contratação (Aguardar correções do Instrutor)',
                                        ])
                                        ->required()
                                ]);

                            return $schema;
                        })
                        ->action(function (Instrutores $record, array $data) {

                            $documentos = $record->documentosContratacao;
                            foreach ($documentos as $doc) {
                                $statusCampo = "status_doc_{$doc->id}";
                                $motivoCampo = "motivo_doc_{$doc->id}";

                                if (isset($data[$statusCampo])) {
                                    $doc->update([
                                        'status_aprovacao' => $data[$statusCampo],
                                        'motivo_rejeicao' => $data[$statusCampo] === 'rejeitado' ? ($data[$motivoCampo] ?? null) : null,
                                    ]);
                                }
                            }

                            $novoStatus = $data['decisao_final'] === 'aprovado' ? 'aprovado' : 'pendente_instrutor';
                            $record->update(['status_documentacao' => $novoStatus]);

                            if ($novoStatus === 'pendente_instrutor') {
                                Notification::make()
                                    ->title('Documentação Devolvida para Ajustes')
                                    ->body("Alguns documentos da sua contratação para o curso '{$record->curso->titulo}' requerem correção. Acesse o painel para verificar.")
                                    ->warning()
                                    ->sendToDatabase($record->instrutor);
                            }

                            Notification::make()
                                ->title('Análise concluída com sucesso!')
                                ->success()
                                ->send();
                        }),

                ])
                    ->size('lg')
                    ->color('info')
                    ->tooltip('Ações')
                    ->icon('heroicon-o-bars-arrow-down')
                    ->tooltip('Ações'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstrutores::route('/'),
        ];
    }
}
