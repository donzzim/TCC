<?php

namespace App\Services;

use App\Models\Escola\Instrutores;
use App\Models\Contratacao\ContratacaoDocumento;
use App\Models\Contratacao\ProcessoDocumento;
use App\Models\Contratacao\ModalidadeContratacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class DocumentoContratacaoService
{
    /**
     * Gera os documentos de aceite baseados na Modalidade de Contratação escolhida.
     */
    public function gerarPendenciasParaInstrutor(Instrutores $instrutorPivot, int $modalidadeId): void
    {
        $documentosCatalogo = ContratacaoDocumento::with('modelo')
            ->where('modelable_type', ModalidadeContratacao::class)
            ->where('modelable_id', $modalidadeId)
            ->get();

        if ($documentosCatalogo->isEmpty()) {
            throw new Exception("Nenhum documento configurado no catálogo para a modalidade selecionada.");
        }

        foreach ($documentosCatalogo as $catalogo) {
            if (!$catalogo->modelo) {
                continue;
            }

            $conteudoProcessado = $this->parseVariaveis($catalogo->modelo->conteudo, $instrutorPivot);

            ProcessoDocumento::create([
                // AQUI FOI CORRIGIDO DE esc_instrutor_id PARA instrutor_id conforme o log
                'instrutor_id' => $instrutorPivot->id,
                'modelo_id' => $catalogo->modelo->id,
                'conteudo_gerado' => $conteudoProcessado,
                'status_aprovacao' => 'pendente',
                'is_assinado' => false,
                'atualizado_por' => Auth::id(),
            ]);
        }
    }

    private function parseVariaveis(string $texto, Instrutores $instrutorPivot): string
    {
        $curso = $instrutorPivot->curso;
        $instrutor = $instrutorPivot->instrutor;

        $variaveis = [
            '{{INSTRUTOR_NOME}}' => $instrutor->name,
            '{{INSTRUTOR_CPF}}' => $this->formatarCpf($instrutor->cpf),
            '{{INSTRUTOR_RG}}' => $instrutorPivot->perfil->rg ?? '___________',
            '{{INSTRUTOR_ENDERECO}}' => $this->formatarEndereco($instrutorPivot->perfil->endereco ?? null),
            '{{CURSO_TITULO}}' => $curso->titulo,
            '{{CURSO_DATA}}' => Carbon::parse($curso->inicio)->format('d/m/Y'),
            '{{CURSO_CARGA_HORARIA}}' => $instrutorPivot->carga_horaria ?? '0',
            '{{PJ_RAZAO_SOCIAL}}' => $instrutorPivot->razao_social ?? '___________',
            '{{PJ_CNPJ}}' => $instrutorPivot->cnpj ?? '___________',
            '{{PJ_ENDERECO}}' => '___________',
            '{{DATA_ATUAL_EXTENSO}}' => 'Vitória/ES, ' . now()->translatedFormat('d \d\e F \d\e Y'),
        ];

        return str_replace(array_keys($variaveis), array_values($variaveis), $texto);
    }

    private function formatarCpf(?string $cpf): string
    {
        if (!$cpf || strlen($cpf) !== 11) return $cpf ?? '___________';
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpf);
    }

    private function formatarEndereco(?array $endereco): string
    {
        if (!$endereco) return '___________';
        return collect([
            $endereco['logradouro'] ?? '',
            $endereco['numero'] ?? '',
            $endereco['bairro'] ?? '',
            $endereco['cidade'] ?? '',
            $endereco['uf'] ?? ''
        ])->filter()->join(', ');
    }
}
