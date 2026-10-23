<?php

namespace App\Filament\Egap\Auth;

use App\Filament\Egap\Auth\Concerns\ResolveLotacaoEgap;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\MobileSessao;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserInfo;
use App\Models\egap\UserLotacao;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Contraparte mobile da EgapSession: o mesmo bloco (egap_id, info, lotação),
 * guardado por token Sanctum em egap_mobile_sessoes em vez de na sessão web.
 * Uma instância por requisição; as lotações são consultadas uma única vez.
 */
class MobileEgapSession
{
    use ResolveLotacaoEgap;

    protected ?User $user = null;

    protected ?Collection $lotacoes = null;

    protected bool $legada = false;

    protected ?int $setorLegado = null;

    protected ?int $unidadeLegada = null;

    protected bool $lotacaoResolvida = false;

    protected ?array $lotacao = null;

    protected function __construct(protected MobileSessao $sessao) {}

    public static function iniciar(User $user, PersonalAccessToken $token, ?string $dispositivo = null): self
    {
        $sessao = MobileSessao::query()->create([
            'token_id' => $token->getKey(),
            'user_id' => $user->getKey(),
            'egap_id' => static::egapIdPorCpf($user->cpf),
            'info' => [
                'nome' => $user->name,
                'cpf' => $user->cpf,
                'cargo' => UserInfo::query()->where('user_id', $user->getKey())->value('cargo'),
            ],
            'dispositivo' => $dispositivo,
        ]);

        $instancia = new static($sessao);
        $instancia->user = $user;
        $instancia->lotacao(); // escolhe e persiste a lotação inicial

        return $instancia;
    }

    public static function doToken(PersonalAccessToken $token): ?self
    {
        $sessao = MobileSessao::query()->where('token_id', $token->getKey())->first();

        return $sessao ? new static($sessao) : null;
    }

    public function user(): User
    {
        return $this->user ??= User::query()->findOrFail($this->sessao->user_id);
    }

    public function resolverAtual(): ?int
    {
        return $this->sessao->egap_id;
    }

    public function info(): array
    {
        return $this->sessao->info ?? [];
    }

    public function lotacoes(): Collection
    {
        if ($this->lotacoes !== null) {
            return $this->lotacoes;
        }

        $lotacoes = static::lotacoesDoUsuario($this->sessao->user_id);

        if ($lotacoes->isEmpty() && filled($this->sessao->egap_id)) {
            $legada = UserEgap::query()->find($this->sessao->egap_id)?->lotacoes()->first();
            $setor = $legada ? Setores::query()->find($legada->setor) : null;

            if ($setor) {
                $this->legada = true;
                $this->setorLegado = (int) $setor->id;
                $this->unidadeLegada = filled($legada->unidade_judiciaria) ? (int) $legada->unidade_judiciaria : null;
                $lotacoes = collect([$setor]);
            }
        }

        return $this->lotacoes = $lotacoes;
    }

    public function usaLotacaoLegada(): bool
    {
        $this->lotacoes();

        return $this->legada;
    }

    /**
     * @return array{setor: int, codigodauo: ?int}|null
     */
    public function lotacao(): ?array
    {
        if ($this->lotacaoResolvida) {
            return $this->lotacao;
        }

        $lotacoes = $this->lotacoes();

        $lotacao = $this->legada
            ? ['setor' => $this->setorLegado, 'codigodauo' => $this->unidadeLegada]
            : static::escolherLotacao($lotacoes, $this->sessao->setor_id);

        $this->persistir($lotacao);

        $this->lotacaoResolvida = true;

        return $this->lotacao = $lotacao;
    }

    public function setorAtual(): ?int
    {
        return $this->lotacao()['setor'] ?? null;
    }

    public function unidadeAtual(): ?int
    {
        return $this->lotacao()['codigodauo'] ?? null;
    }

    /**
     * @return array{setor: ?string, unidade: ?string, codigodauo: ?int}
     */
    public function rotulosLotacaoAtual(): array
    {
        $setor = filled($id = $this->setorAtual())
            ? $this->lotacoes()->firstWhere('id', $id)
            : null;

        return [
            'setor' => $setor?->setor,
            'unidade' => $setor?->unidadeorganizacional,
            'codigodauo' => $this->unidadeAtual(),
        ];
    }

    public function trocarSetor(int $setor): void
    {
        if ($this->lotacoes()->doesntContain('id', $setor)) {
            throw ValidationException::withMessages([
                'setor_id' => 'O setor informado não pertence às suas lotações.',
            ]);
        }

        $this->lotacaoResolvida = false;
        $this->sessao->setor_id = $setor;
        $this->lotacao();
    }

    /**
     * @return array{user_id: int, id_egap: ?int, setor: int, unidade_judiciaria: int}|null
     */
    public function escopo(): ?array
    {
        $lotacao = $this->lotacao();

        if ($lotacao === null || blank($lotacao['codigodauo'] ?? null)) {
            return null;
        }

        return [
            'user_id' => (int) $this->sessao->user_id,
            'id_egap' => $this->sessao->egap_id,
            'setor' => $lotacao['setor'],
            'unidade_judiciaria' => (int) $lotacao['codigodauo'],
        ];
    }

    public function encerrar(): void
    {
        $this->sessao->token?->delete();
    }

    public function paraResposta(): array
    {
        $user = $this->user();
        $info = $this->info();
        $rotulos = $this->rotulosLotacaoAtual();
        $atual = $this->lotacao();

        $principais = $this->usaLotacaoLegada()
            ? collect()
            : UserLotacao::query()->where('user_id', $user->getKey())->pluck('principal', 'setor_id');

        return [
            'usuario' => [
                'id' => $user->getKey(),
                'nome' => $info['nome'] ?? $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'cpf' => $info['cpf'] ?? $user->cpf,
                'cargo' => $info['cargo'] ?? null,
            ],
            'egap_id' => $this->sessao->egap_id,
            'setor_atual' => $atual === null ? null : [
                'id' => $atual['setor'],
                'setor' => $rotulos['setor'],
                'unidade' => $rotulos['unidade'],
                'codigodauo' => $atual['codigodauo'],
            ],
            'lotacoes' => $this->lotacoes()->map(fn (Setores $setor): array => [
                'id' => (int) $setor->id,
                'setor' => $setor->setor,
                'unidade' => $setor->unidadeorganizacional,
                'codigodauo' => $this->legada
                    ? $this->unidadeLegada
                    : (filled($setor->codigodauo) ? (int) $setor->codigodauo : null),
                'principal' => (bool) ($principais[$setor->id] ?? false),
            ])->values()->all(),
            'lotacao_legada' => $this->legada,
        ];
    }

    protected function persistir(?array $lotacao): void
    {
        $setor = $lotacao['setor'] ?? null;
        $unidade = $lotacao['codigodauo'] ?? null;

        $this->sessao->forceFill(['setor_id' => $setor, 'codigodauo' => $unidade]);

        if ($this->sessao->isDirty()) {
            $this->sessao->save();
        }
    }
}
