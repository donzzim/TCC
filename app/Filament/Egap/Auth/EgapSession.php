<?php

namespace App\Filament\Egap\Auth;

use App\Helper\CpfHelper;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserInfo;
use App\Models\egap\UserLotacao;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 *   egap_session
 *   ├── user_id ...... users.id do usuário logado (dono do bloco)
 *   ├── egap_id ...... egap_mat_infousers.usuario_id com o mesmo CPF
 *   ├── lotacao ...... null quando o usuário não tem lotação
 *   │   ├── setor ........ setores.id escolhido no select do topo
 *   │   └── codigodauo ... setores.codigodauo desse setor
 *   └── info
 *       ├── nome ..... users.name
 *       ├── cpf ...... users.cpf
 *       └── cargo .... user_perfil.cargo
 */
class EgapSession
{
    public const SESSION_KEY = 'egap_session';

    public const SESSION_KEY_SETOR = 'setorprincipal';

    public static function resolverAtual(): ?int
    {
        return static::lembrar('egap_id', fn (): ?int => static::consultarPorCpf(auth(User::GUARD)->user()?->cpf));
    }

    public static function escopo(Builder $query, string $coluna): Builder
    {
        $idEgap = static::resolverAtual();

        return blank($idEgap)
            ? $query->whereRaw('1 = 0')
            : $query->where($coluna, $idEgap);
    }

    public static function limpar(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function info(): ?array
    {
        return static::lembrar('info', function (): array {
            $user = auth(User::GUARD)->user();

            return [
                'nome' => $user->name,
                'cpf' => $user->cpf,
                'cargo' => UserInfo::query()->where('user_id', $user->getKey())->value('cargo'),
            ];
        });
    }

    public static function cargoAtual(): ?string
    {
        return static::info()['cargo'] ?? null;
    }

    public static function lotacoes(): Collection
    {
        $userId = auth(User::GUARD)->id();

        if (blank($userId)) {
            return collect();
        }

        return UserLotacao::query()
            ->doUsuario($userId)
            ->with('setor')
            ->get()
            ->pluck('setor')
            ->filter()
            ->values();
    }

    public static function lotacao(): ?array
    {
        $bloco = static::bloco();

        if ($bloco === null) {
            return null;
        }

        $escolhido = filled(session(self::SESSION_KEY_SETOR)) ? (int) session(self::SESSION_KEY_SETOR) : null;

        if (array_key_exists('lotacao', $bloco) && ($bloco['lotacao']['setor'] ?? null) === $escolhido) {
            return $bloco['lotacao'];
        }

        $lotacao = static::consultarLotacao($escolhido);

        if ($lotacao !== null && $lotacao['setor'] !== $escolhido) {
            session([self::SESSION_KEY_SETOR => $lotacao['setor']]);
        }

        session([self::SESSION_KEY . '.lotacao' => $lotacao]);

        return $lotacao;
    }

    public static function setorAtual(): ?int
    {
        return static::lotacao()['setor'] ?? null;
    }

    public static function unidadeAtual(): ?int
    {
        return static::lotacao()['codigodauo'] ?? null;
    }

    public static function lotacaoUtilizavelComoDestino(): bool
    {
        $unidade = static::unidadeAtual();

        if (blank($unidade) || blank(static::setorAtual())) {
            return false;
        }

        return Setores::query()
            ->whereKey($unidade)
            ->whereColumn('id', 'codigopai')
            ->exists();
    }

    public static function rotulosLotacaoAtual(): array
    {
        $setor = filled($id = static::setorAtual())
            ? Setores::query()->whereKey($id)->first(['setor', 'unidadeorganizacional', 'codigodauo'])
            : null;

        return [
            'setor' => $setor?->setor,
            'unidade' => $setor?->unidadeorganizacional,
            'codigodauo' => $setor?->codigodauo,
        ];
    }

    public static function formatosDeBusca(?string $cpf): array
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digitos) !== 11) {
            return [];
        }

        return [CpfHelper::format($digitos), $digitos];
    }

    protected static function bloco(): ?array
    {
        $usuarioAtual = auth(User::GUARD)->id();

        if (blank($usuarioAtual)) {
            return null;
        }

        $bloco = session(self::SESSION_KEY);

        if (is_array($bloco) && ($bloco['user_id'] ?? null) === $usuarioAtual) {
            return $bloco;
        }

        $bloco = ['user_id' => $usuarioAtual];
        session([self::SESSION_KEY => $bloco]);

        return $bloco;
    }

    protected static function lembrar(string $chave, Closure $calcular): mixed
    {
        $bloco = static::bloco();

        if ($bloco === null) {
            return null;
        }

        if (array_key_exists($chave, $bloco)) {
            return $bloco[$chave];
        }

        $valor = $calcular();
        session([self::SESSION_KEY . '.' . $chave => $valor]);

        return $valor;
    }
    protected static function consultarPorCpf(?string $cpf): ?int
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

    /**
     * @return array{setor: int, codigodauo: ?int}|null
     */
    protected static function consultarLotacao(?int $escolhido): ?array
    {
        $lotacoes = static::lotacoes();

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
