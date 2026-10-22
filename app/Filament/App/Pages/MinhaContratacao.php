<?php

namespace App\Filament\App\Pages;

use App\Models\Contratacao\ModalidadeContratacao;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Escola\Instrutores;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class MinhaContratacao extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Minhas Contratações';

    protected static ?string $title = 'Preenchimento de Documentação';

    protected string $view = 'filament.app.pages.minha-contratacao';

    // Removemos a página do menu lateral se não houver contratação ativa,
    // ou deixamos visível para ele ver o histórico.
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public $contratacao;

    public $modalidadeDescricao = 'Não definida';

    public function mount(Request $request)
    {
        // 1. Array de relacionamentos que PRECISAMOS carregar
        // Note a mudança de 'processoCursoCapa' para 'processosCursos'
        $relacionamentos = ['processosCursos.processo.modalidade', 'curso'];

        // Se a URL trouxer o ID da contratação (?id=1)
        if ($request->has('id')) {
            $this->contratacao = Instrutores::with($relacionamentos)
                ->where('id', $request->id)
                ->where('instrutor_id', Filament::auth()->id())
                ->firstOrFail();
        } else {
            // Busca a contratação mais recente pendente ou em análise
            $this->contratacao = Instrutores::with($relacionamentos)
                ->where('instrutor_id', Filament::auth()->id())
                ->whereIn('status_documentacao', ['pendente_instrutor', 'em_analise', 'devolvido'])
                ->latest()
                ->first();
        }

        if (! $this->contratacao) {
            session()->flash('info', 'Você não possui documentações de contratação pendentes no momento.');
            redirect()->route('filament.app.pages.dashboard');

            return; // Importante adicionar o return aqui após o redirecionamento
        }

        // Resolve a modalidade na mão, varrendo o array carregado e passando para a view
        $processoCurso = ProcessoCursos::query()
            ->where('instrutor_id', $this->contratacao->instrutor_id)
            ->where('curso_id', $this->contratacao->curso_id)
            ->first();

        if ($processoCurso) {
            $modalidadeId = Processo::query()
                ->whereKey($processoCurso->getAttribute('processo_id'))
                ->value('modalidade_id');
            $descricao = ModalidadeContratacao::query()
                ->whereKey($modalidadeId)
                ->value('descricao');

            if (is_string($descricao)) {
                $this->modalidadeDescricao = $descricao;
            }
        }
    }
}
