<?php

namespace App\Livewire;

use App\Models\Documentacao;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class BotaoAjudaGlobal extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public $rotaAtual;
    public $documentacao;
    public $historico;
    public ?array $data = [];

    public function limpar()
    {
        $this->rotaAtual;
        $this->documentacao = null;
        $this->historico;
        $this->data = [];
        $this->form->fill([]);
    }
    public function mount()
    {
        $this->rotaAtual = session('filament_rota_atual', 'global');
        $this->carregarDocumentacao();
    }
    public function carregarDocumentacao()
    {
        $this->limpar();
        $rotaAtual = session('filament_rota_atual', 'global');;
        $this->documentacao = Documentacao::where('rota', $rotaAtual)->first();

        if ($this->documentacao) {
            $this->historico = $this->documentacao
                ? $this->documentacao->historicos()->latest()->get()
                : collect();

            $this->form->fill([
                'rota' => $rotaAtual,
                'titulo' => $this->documentacao->titulo,
                'conteudo' => $this->documentacao->conteudo,
            ]);
        } else {
            $this->form->fill([
                'rota' => $rotaAtual,
                'titulo' => 'Nova Documentação',
                'conteudo' => '',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.botao-ajuda-global');
    }

    public function helpAction(): Action
    {
        return Action::make('help')
            ->label('Ajuda / Documentação')
            ->icon('heroicon-o-question-mark-circle')
            ->iconButton()
            ->color('info')
            ->tooltip('Documentação do módulo')
            ->slideOver()
            ->modalSubmitAction(false)
            ->mountUsing(function () {
                $this->carregarDocumentacao();
            })
            ->modalContent(function () {
                return view('partials.wiki-modal-content');
            })
        ;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rota')
                    ->readOnly(),
                TextInput::make('titulo')
                    ->required()
                    ->label('Título do Tópico'),

                RichEditor::make('conteudo')
                    ->label('Conteúdo da Wiki')
                    ->fileAttachmentsDisk('public') // Permite upload de imagens/vídeos
                    ->fileAttachmentsDirectory('wiki-uploads') // Pasta no storage
                    ->fileAttachmentsVisibility('public')
                    ->toolbarButtons([
                        'attachFiles', // Botão de upload
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link', // Permite colar links (YouTube embeda automático as vezes ou cria link)
                        'orderedList',
                        'redo',
                        'strike',
                        'undo',
                    ])
                    ->columnSpanFull()
            ])
            ->statePath('data');
    }

    public function salvarWiki()
    {
        $dados = $this->form->getState();
        if ($dados['conteudo']) {
            $doc = Documentacao::firstOrNew(['rota' => $dados['rota']]);
            if ($doc->exists) {
                $doc->historicos()->create([
                    'user_id' => Auth::id(),
                    'conteudo_anterior' => $doc->conteudo,
                ]);
            } else {
                $doc->ativo = true;
            }

            $doc->titulo = $dados['titulo'];
            $doc->conteudo = $dados['conteudo'];
            $doc->user_id = Auth::id();
            $doc->save();

            $this->documentacao = $doc;

            \Filament\Notifications\Notification::make()
                ->title('Documentação atualizada com sucesso!')
                ->success()
                ->send();
        }
    }
}
