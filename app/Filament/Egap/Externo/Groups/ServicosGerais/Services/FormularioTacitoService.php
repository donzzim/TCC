<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Services;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusResposta;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\ServicosGerais\Contrato;
use App\Models\egap\ServicosGerais\Formulario;

/**
 * Aplica "Tácito" nos formulários abertos e sem resposta de um contrato
 * (legado: avatacito.php).
 *
 * Simplificação deliberada: o legado, para 'co'/'te', recalculava o status
 * final multiplicando os `status` de todas as respostas
 * (`gmp_div_r($produto, 2)`) — um truque frágil que dependia de nenhuma
 * resposta nunca assumir um valor par por acidente. Aqui a regra é
 * explícita: sem fiscal atribuído -> Tácito; com fiscal atribuído mas sem
 * resposta -> volta para avaliação do gestor, sem tentar inferir uma nota.
 * Precisa ser confirmado com quem entende a regra de glosa original.
 */
class FormularioTacitoService
{
    /**
     * @return array{tacitos: int, aguardando_avaliacao: int}
     */
    public function aplicar(Contrato $contrato): array
    {
        $gestorId = UsuarioSelecionado::resolverAtual();

        $pendentes = Formulario::query()
            ->whereHas('periodo', fn($query) => $query->where('contrato_id', $contrato->id))
            ->where('status', StatusFormulario::Aberto)
            ->get();

        $tacitos = 0;
        $aguardandoAvaliacao = 0;

        foreach ($pendentes as $formulario) {
            if (blank($formulario->fiscal_id)) {
                $formulario->update([
                    'status' => StatusFormulario::Tacito,
                    'gestor_id' => $gestorId,
                ]);

                $formulario->respostas()->update(['status' => StatusResposta::Validada]);

                $tacitos++;

                continue;
            }

            $formulario->update([
                'status' => StatusFormulario::AguardandoAvaliacaoGestor,
                'gestor_id' => $gestorId,
            ]);

            $aguardandoAvaliacao++;
        }

        return [
            'tacitos' => $tacitos,
            'aguardando_avaliacao' => $aguardandoAvaliacao,
        ];
    }
}
