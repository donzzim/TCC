<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Services;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusResposta;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\ServicosGerais\Formulario;
use App\Models\egap\ServicosGerais\FormularioResposta;
use App\Models\egap\ServicosGerais\Pergunta;
use InvalidArgumentException;

/**
 * Ações do gestor e do fiscal sobre um {@see Formulario} já gerado (legado:
 * avalg.php + avaledit.php + a parte de gestor de avatransporte_r.php).
 */
class FormularioAvaliacaoService
{
    /**
     * Fiscal responde o questionário de um formulário (legado: alvo do form
     * de avalg.php, "index.php?p=avalpg" — não incluído nos arquivos
     * fornecidos; construído aqui a partir da estrutura de leitura de
     * avalg.php/avaledit.php).
     *
     * @param  array<int, array{estrelas: int, observacao?: string|null}>  $respostasPorPergunta  chave = pergunta_id
     */
    public function responderComoFiscal(
        Formulario $formulario,
        array $respostasPorPergunta,
        bool $temFaltaSemSubstituicao,
        ?string $observacaoFalta,
    ): void {
        $perguntas = Pergunta::query()
            ->whereIn('id', array_keys($respostasPorPergunta))
            ->get()
            ->keyBy('id');

        foreach ($respostasPorPergunta as $perguntaId => $dados) {
            $pergunta = $perguntas->get($perguntaId);
            $estrelas = (int) $dados['estrelas'];
            $pontos = $pergunta ? (int) round($estrelas / 5 * $pergunta->peso_maximo) : null;

            FormularioResposta::query()
                ->where('formulario_id', $formulario->id)
                ->where('pergunta_id', $perguntaId)
                ->update([
                    'estrelas' => $estrelas,
                    'pontos' => $pontos,
                    'observacao' => $dados['observacao'] ?? null,
                    'status' => StatusResposta::AguardandoValidacao->value,
                    'date_time' => now(),
                ]);
        }

        $formulario->update([
            'fiscal_id' => UsuarioSelecionado::resolverAtual(),
            'tem_falta_sem_substituicao' => $temFaltaSemSubstituicao,
            'observacao_falta' => $observacaoFalta,
            'status' => StatusFormulario::AguardandoAvaliacaoGestor,
        ]);
    }

    public function definirNotaGestor(Formulario $formulario, int $nota): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new InvalidArgumentException('A nota do gestor deve estar entre 0 e 10.');
        }

        $formulario->update([
            'nota_gestor' => $nota,
            'gestor_id' => UsuarioSelecionado::resolverAtual(),
        ]);
    }

    public function validarResposta(FormularioResposta $resposta, StatusResposta $novoStatus): void
    {
        $resposta->update(['status' => $novoStatus]);

        $this->recalcularStatusFormulario($resposta->formulario);
    }

    /**
     * Reabre o formulário para o fiscal responder de novo (legado:
     * avaledit.php `reabrir`/avalg.php `reabrir` — zera as respostas, já que
     * o fiscal vai preenchê-las de novo).
     */
    public function reabrir(Formulario $formulario): void
    {
        $formulario->update([
            'status' => StatusFormulario::Aberto,
            'tem_falta_sem_substituicao' => false,
            'observacao_falta' => null,
            'gestor_id' => UsuarioSelecionado::resolverAtual(),
        ]);

        $formulario->respostas()->update([
            'status' => StatusResposta::Pendente->value,
            'estrelas' => null,
            'pontos' => null,
            'observacao' => null,
        ]);
    }

    /**
     * Recalcula o status do formulário a partir do status de cada resposta:
     * enquanto alguma ainda estiver aguardando validação, o formulário
     * permanece "Aguardando Avaliação do Gestor"; só fecha como
     * Validado/Invalidado quando todas tiverem sido decididas.
     */
    private function recalcularStatusFormulario(Formulario $formulario): void
    {
        $respostas = $formulario->respostas()->get();

        if ($respostas->contains(fn(FormularioResposta $resposta): bool => $resposta->status === StatusResposta::AguardandoValidacao)) {
            return;
        }

        $formulario->update([
            'status' => $respostas->contains(fn(FormularioResposta $resposta): bool => $resposta->status === StatusResposta::Invalidada)
                ? StatusFormulario::Invalidado
                : StatusFormulario::Validado,
        ]);
    }
}
