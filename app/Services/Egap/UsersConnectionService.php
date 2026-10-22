<?php

namespace App\Services\Egap;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserMobile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsersConnectionService
{
    public function findByCpf(string $cpf): ?UserMobile
    {
        $normalizedCpf = $this->normalizeCpf($cpf);

        if ($normalizedCpf === '') {
            return null;
        }

        $user = $this->findLocalUserByCpf($normalizedCpf);
        if (! $user) {
            return null;
        }

        $infoUser = $this->findInfoUserByCpf($normalizedCpf);
        if (! $infoUser) {
            return null;
        }

        return $this->buildMobileSessionUser($user, $infoUser);
    }

    /**
     * Aceita também outro model de `users`: tokens emitidos antes da troca
     * para App\Models\egap\User ainda devolvem App\Models\User no
     * `$request->user()`. O registro é reaberto como usuário do EGAP pelo id.
     */
    public function findByUser(int|Model|null $user): ?UserMobile
    {
        $localUser = match (true) {
            $user instanceof User => $user,
            $user instanceof Model => User::query()->find($user->getKey()),
            default => User::query()->find($user),
        };

        if (! $localUser) {
            return null;
        }

        if (! $localUser->cpf) {
            return null;
        }

        return $this->findByCpf((string) $localUser->cpf);
    }

    public function findByEgapUser(int|UserEgap $userEgap): ?UserMobile
    {
        $egapUser = $userEgap instanceof UserEgap
            ? $this->loadEgapUserRelations($userEgap)
            : $this->findEgapUserById($userEgap);

        if (! $egapUser) {
            return null;
        }

        $infoUser = $egapUser->infoUser;

        if (! $infoUser?->cpf) {
            return null;
        }

        $localUser = $this->findLocalUserByCpf($this->normalizeCpf((string) $infoUser->cpf));

        if (! $localUser) {
            return null;
        }

        return $this->buildMobileSessionUser($localUser, $infoUser);
    }

    public function findByLogin(string $login): ?UserMobile
    {
        $trimmedLogin = trim($login);

        if ($trimmedLogin === '') {
            return null;
        }

        $loginCpfCandidates = $this->cpfCandidates($this->normalizeCpf($trimmedLogin));

        $localUser = User::query()
            ->where(function (Builder $query) use ($trimmedLogin, $loginCpfCandidates): void {
                $query
                    ->where('login', $trimmedLogin)
                    ->orWhere('email', $trimmedLogin);

                if ($loginCpfCandidates !== []) {
                    $query->orWhereIn('cpf', $loginCpfCandidates);
                }
            })
            ->first();

        if ($localUser?->cpf) {
            return $this->findByCpf((string) $localUser->cpf);
        }

        $egapUser = UserEgap::query()
            ->where(function (Builder $query) use ($trimmedLogin): void {
                $query
                    ->where('username', $trimmedLogin)
                    ->orWhere('email', $trimmedLogin);
            })
            ->first();

        if (! $egapUser) {
            return null;
        }

        return $this->findByEgapUser($egapUser);
    }

    private function buildMobileSessionUser(User $user, InfoUser $infoUser): UserMobile
    {
        $lotacao = $this->findLatestLotacaoByInfoUser($infoUser);

        return UserMobile::fromLinkedUsers($user, $infoUser, $lotacao);
    }

    private function findLocalUserByCpf(string $normalizedCpf): ?User
    {
        return User::query()
            ->whereNotNull('cpf')
            ->whereIn('cpf', $this->cpfCandidates($normalizedCpf))
            ->first();
    }

    private function findInfoUserByCpf(string $normalizedCpf): ?InfoUser
    {
        return InfoUser::query()
            ->whereNotNull('cpf')
            ->whereNotNull('usuario_id')
            ->whereIn('cpf', $this->cpfCandidates($normalizedCpf))
            ->orderByDesc('date_time')
            ->first();
    }

    private function findLatestLotacaoByInfoUser(InfoUser $infoUser): ?Lotacao
    {
        if (! $infoUser->usuario_id) {
            return null;
        }

        return Lotacao::query()
            ->where('id_user', (int) $infoUser->usuario_id)
            ->orderByDesc('date_time')
            ->orderByDesc('id')
            ->first();
    }

    private function findEgapUserById(int $id): ?UserEgap
    {
        $userEgap = UserEgap::query()->find($id);

        return $userEgap ? $this->loadEgapUserRelations($userEgap) : null;
    }

    private function loadEgapUserRelations(UserEgap $userEgap): UserEgap
    {
        $userEgap->loadMissing([
            'infoUser',
        ]);

        return $userEgap;
    }

    private function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D+/', '', trim($cpf)) ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function cpfCandidates(string $normalizedCpf): array
    {
        if ($normalizedCpf === '') {
            return [];
        }

        $candidates = [$normalizedCpf];

        if (strlen($normalizedCpf) === 11) {
            $candidates[] = sprintf(
                '%s.%s.%s-%s',
                substr($normalizedCpf, 0, 3),
                substr($normalizedCpf, 3, 3),
                substr($normalizedCpf, 6, 3),
                substr($normalizedCpf, 9, 2),
            );
        }

        return array_values(array_unique($candidates));
    }
}
