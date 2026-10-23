<?php

namespace App\Services\Egap\Inventario;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\User;
use App\Models\egap\UserLotacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Quem está lotado nos setores de um inventário.
 *
 * Fonte principal: user_lotacao. Quem não tem nenhuma linha lá cai na lotação legada
 * (egap_mat_lotacao mais recente do usuário do EGAP antigo com o mesmo CPF).
 */
class DestinatariosInventario
{
    private const LOTE = 1000;

    /**
     * @param  Collection<int, int>  $setorIds
     * @return Collection<int, array<int, int>> chave = users.id, valor = setores cobertos em que está lotado
     */
    public static function porSetor(Collection $setorIds): Collection
    {
        $setorIds = $setorIds->map(fn ($id): int => (int) $id)->unique()->values();
        $destinatarios = [];

        foreach ($setorIds->chunk(self::LOTE) as $lote) {
            UserLotacao::query()
                ->join('users', 'users.id', '=', 'user_lotacao.user_id')
                ->where('users.ativo', 1)
                ->whereIn('user_lotacao.setor_id', $lote->all())
                ->get(['user_lotacao.user_id', 'user_lotacao.setor_id'])
                ->each(function (UserLotacao $linha) use (&$destinatarios): void {
                    $destinatarios[(int) $linha->user_id][] = (int) $linha->setor_id;
                });
        }

        foreach (static::pelaLotacaoLegada($setorIds) as $userId => $setorId) {
            $destinatarios[$userId][] = $setorId;
        }

        return collect($destinatarios)->map(fn (array $setores): array => array_values(array_unique($setores)));
    }

    /**
     * Usuários ativos sem user_lotacao cuja lotação legada atual está num dos setores.
     *
     * @return array<int, int> users.id => setor
     */
    private static function pelaLotacaoLegada(Collection $setorIds): array
    {
        // lotação legada atual (a mais recente) de cada usuário do EGAP antigo, só nos setores cobertos
        $setorPorEgapId = [];

        foreach ($setorIds->chunk(self::LOTE) as $lote) {
            // só quem já passou por algum desses setores; a linha nº 1 (mais recente) diz onde está hoje
            $historico = Lotacao::query()
                ->select(['id_user', 'setor'])
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY id_user ORDER BY date_time DESC, id DESC) AS ordem')
                ->whereIn('id_user', Lotacao::query()->select('id_user')->whereIn('setor', $lote->all()));

            DB::connection((new Lotacao)->getConnectionName())
                ->query()
                ->fromSub($historico, 'l')
                ->where('l.ordem', 1)
                ->whereIn('l.setor', $lote->all())
                ->get(['l.id_user', 'l.setor'])
                ->each(function ($linha) use (&$setorPorEgapId): void {
                    $setorPorEgapId[(int) $linha->id_user] = (int) $linha->setor;
                });
        }

        if ($setorPorEgapId === []) {
            return [];
        }

        $formatos = [];

        foreach (array_chunk(array_keys($setorPorEgapId), self::LOTE) as $lote) {
            foreach (InfoUser::query()->whereIn('usuario_id', $lote)->pluck('cpf') as $cpf) {
                array_push($formatos, ...EgapSession::formatosDeBusca($cpf));
            }
        }

        $formatos = array_values(array_unique($formatos));
        $resultado = [];

        foreach (array_chunk($formatos, self::LOTE) as $lote) {
            User::query()
                ->where('ativo', 1)
                ->whereIn('cpf', $lote)
                ->whereDoesntHave('lotacoes')
                ->get(['id', 'cpf'])
                ->each(function (User $user) use ($setorPorEgapId, &$resultado): void {
                    // mesmo critério de vínculo do login (o primeiro cadastro com o CPF)
                    $egapId = EgapSession::egapIdPorCpf($user->cpf);

                    if ($egapId !== null && isset($setorPorEgapId[$egapId])) {
                        $resultado[(int) $user->id] = $setorPorEgapId[$egapId];
                    }
                });
        }

        return $resultado;
    }
}
