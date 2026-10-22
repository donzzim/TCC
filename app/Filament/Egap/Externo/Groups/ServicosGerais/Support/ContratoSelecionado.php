<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Support;

use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\ServicosGerais\ContratoGestor;
use Illuminate\Support\Collection;

/**
 * Identifica o contrato que o gestor logado está gerenciando na sessão atual
 * (legado: $_SESSION["csg_contrato_selecionado"]) e resolve quais contratos
 * o usuário autenticado pode gerir (legado: ava_funcao WHERE id_usuario=?
 * AND id_tipo='G' — sem o `$ngestor = 551` fixo que existia em
 * avagestor.php).
 *
 * O usuário em si vem de {@see UsuarioSelecionado}, o mesmo mecanismo de
 * identificação já usado no restante do Ambiente Externo, em vez de
 * `UserEgap::currentAuthenticated()` direto.
 */
class ContratoSelecionado
{
    public const SESSION_KEY = 'externo.servicos_gerais.contrato_selecionado';

    public static function resolverAtual(): ?int
    {
        $sessao = session(self::SESSION_KEY);

        return filled($sessao) ? (int) $sessao : null;
    }

    public static function selecionar(int $contratoId): void
    {
        session([self::SESSION_KEY => $contratoId]);
    }

    public static function limpar(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * IDs dos contratos que o usuário autenticado gere.
     */
    public static function meusContratosIds(): Collection
    {
        $usuarioId = UsuarioSelecionado::resolverAtual();

        if (blank($usuarioId)) {
            return collect();
        }

        return ContratoGestor::query()
            ->where('user_id', $usuarioId)
            ->pluck('contrato_id');
    }

    public static function podeGerir(int $contratoId): bool
    {
        return self::meusContratosIds()->contains($contratoId);
    }
}
