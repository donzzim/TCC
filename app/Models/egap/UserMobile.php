<?php

namespace App\Models\egap;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class UserMobile extends Model implements Arrayable, JsonSerializable
{
    protected $table = 'users';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    private ?User $baseUser = null;

    private ?InfoUser $infoUserMobile = null;

    private ?Lotacao $lotacaoMobile = null;

    private ?string $plainTextToken = null;

    public static function fromLinkedUsers(User $user, InfoUser $infoUser, ?Lotacao $lotacao): self
    {
        $mobileUser = new self;
        $mobileUser->baseUser = $user;
        $mobileUser->setConnection($user->getConnectionName());
        $mobileUser->setRawAttributes($user->getAttributes(), true);
        $mobileUser->exists = $user->exists;
        $mobileUser->wasRecentlyCreated = $user->wasRecentlyCreated;
        $mobileUser->setMobileContext($infoUser, $lotacao);

        return $mobileUser;
    }

    public function baseUser(): ?User
    {
        return $this->baseUser;
    }

    public function setMobileContext(InfoUser $infoUser, ?Lotacao $lotacao): void
    {
        $this->infoUserMobile = $infoUser;
        $this->lotacaoMobile = $lotacao;
    }

    public function setMobileToken(string $token): void
    {
        $this->plainTextToken = $token;
    }

    public function id(): int
    {
        return (int) $this->getKey();
    }

    public function idEgap(): ?int
    {
        $usuarioId = $this->infoUserMobile()?->usuario_id;

        return $usuarioId === null ? null : (int) $usuarioId;
    }

    public function login(): ?string
    {
        return $this->getAttribute('login');
    }

    public function name(): ?string
    {
        return $this->getAttribute('name');
    }

    public function email(): ?string
    {
        return $this->getAttribute('email');
    }

    public function unidadeJudiciaria(): int|string|null
    {
        return $this->lotacaoMobile()?->unidade_judiciaria;
    }

    public function unidadeJudiciariaNome(): ?string
    {
        return $this->lotacaoMobile()?->unidadeJudiciaria?->setor;
    }

    public function setor(): int|string|null
    {
        return $this->lotacaoMobile()?->setor;
    }

    public function setorNome(): ?string
    {
        return $this->lotacaoMobile()?->setorRef?->setor;
    }

    public function token(): ?string
    {
        return $this->plainTextToken;
    }

    public function can($abilities, $arguments = []): bool
    {
        return $this->baseUser()?->can($abilities, $arguments) ?? false;
    }

    public function hasRole(...$roles): bool
    {
        return $this->baseUser()?->hasRole(...$roles) ?? false;
    }

    public function hasAnyRole(...$roles): bool
    {
        return $this->baseUser()?->hasAnyRole(...$roles) ?? false;
    }

    public function hasAllRoles(...$roles): bool
    {
        return $this->baseUser()?->hasAllRoles(...$roles) ?? false;
    }

    public function getRoleNames()
    {
        return $this->baseUser()?->getRoleNames() ?? collect();
    }

    public function permissions(): EloquentCollection
    {
        return $this->baseUser()?->getAllPermissions() ?? new EloquentCollection;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'idEgap' => $this->idEgap(),
            'login' => $this->login(),
            'name' => $this->name(),
            'email' => $this->email(),
            'unidade_judiciaria' => $this->unidadeJudiciaria(),
            'unidade_judiciaria_nome' => $this->unidadeJudiciariaNome(),
            'setor' => $this->setor(),
            'setor_nome' => $this->setorNome(),
            'roles' => $this->roleNames(),
            'permissions' => $this->permissionNames(),
            'token' => $this->token(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function infoUserMobile(): ?InfoUser
    {
        if ($this->infoUserMobile !== null) {
            return $this->infoUserMobile;
        }

        $cpf = $this->getAttribute('cpf');

        if (blank($cpf)) {
            return null;
        }

        $normalizedCpf = preg_replace('/\D+/', '', (string) $cpf) ?? '';

        if ($normalizedCpf === '') {
            return null;
        }

        // whereRaw necessario: o CPF no banco pode estar formatado de forma
        // inconsistente (com pontos/traço/espaço); normalizeSqlColumn() faz
        // essa limpeza via REPLACE em cadeia, algo que o query builder do
        // Eloquent nao expressa sem SQL bruto.
        $this->infoUserMobile = InfoUser::query()
            ->whereNotNull('cpf')
            ->whereNotNull('usuario_id')
            ->whereRaw($this->normalizeSqlColumn('cpf') . ' = ?', [$normalizedCpf])
            ->orderByDesc('date_time')
            ->first();

        return $this->infoUserMobile;
    }

    /**
     * @return array<int, string>
     */
    private function roleNames(): array
    {
        if (! $this->baseUser || ! method_exists($this->baseUser, 'getRoleNames')) {
            return [];
        }

        return $this->baseUser->getRoleNames()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function permissionNames(): array
    {
        if (! $this->baseUser || ! method_exists($this->baseUser, 'getAllPermissions')) {
            return [];
        }

        return $this->baseUser->getAllPermissions()
            ->pluck('name')
            ->values()
            ->all();
    }

    private function lotacaoMobile(): ?Lotacao
    {
        if ($this->lotacaoMobile !== null) {
            return $this->lotacaoMobile;
        }

        $idEgap = $this->idEgap();

        if ($idEgap === null) {
            return null;
        }

        $this->lotacaoMobile = Lotacao::query()
            ->with([
                'unidadeJudiciaria:id,setor',
                'setorRef:id,setor',
            ])
            ->where('id_user', $idEgap)
            ->orderByDesc('date_time')
            ->orderByDesc('id')
            ->first();

        return $this->lotacaoMobile;
    }

    private function normalizeSqlColumn(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '.', ''), '-', ''), '/', ''), ' ', ''), ',', '')";
    }
}
