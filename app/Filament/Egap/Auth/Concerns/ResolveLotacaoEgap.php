<?php

namespace App\Filament\Egap\Auth\Concerns;

use App\Helper\CpfHelper;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use Illuminate\Support\Collection;

/**
 * Consultas puras de egap_id e lotação, sem sessão nem guard.
 * Compartilhadas pela sessão web (EgapSession) e pela sessão mobile.
 */
trait ResolveLotacaoEgap
{
    public static function formatosDeBusca(?string $cpf): array
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digitos) !== 11) {
            return [];
        }

        return [CpfHelper::format($digitos), $digitos];
    }

    public static function egapIdPorCpf(?string $cpf): ?int
    {
        $formatos = static::formatosDeBusca($cpf);

        if ($formatos === []) {
            return null;
        }

        $idEgap = InfoUser::query()
            ->whereIn('cpf', $formatos)
            ->whereIn('usuario_id', UserEgap::query()->select('id'))
            ->orderBy('id')
            ->value('usuario_id');

        return blank($idEgap) ? null : (int) $idEgap;
    }

    public static function lotacoesDoUsuario(int $userId): Collection
    {
        return UserLotacao::query()
            ->doUsuario($userId)
            ->with('setor')
            ->get()
            ->pluck('setor')
            ->filter()
            ->values();
    }

    /**
     * @return array{setor: int, codigodauo: ?int}|null
     */
    public static function escolherLotacao(Collection $lotacoes, ?int $escolhido): ?array
    {
        if ($lotacoes->isEmpty()) {
            return null;
        }

        $setor = $lotacoes->firstWhere('id', $escolhido) ?? $lotacoes->first();

        return [
            'setor' => (int) $setor->id,
            'codigodauo' => filled($setor->codigodauo) ? (int) $setor->codigodauo : null,
        ];
    }
}
