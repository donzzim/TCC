<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TjesUserProvisioningService
{
    public const PROVISIONAL_EMAIL_PREFIX = 'ldap-pending+';

    /**
     * Cria o usuário EMES somente depois de o bind LDAP já ter sido validado.
     * O perfil e a lotação ficam para a tela de complemento.
     *
     * @param array<string, string> $attributes
     */
    public function provision(string $identifier, array $attributes): User
    {
        return DB::transaction(function () use ($identifier, $attributes): User {
            $existing = $this->findExistingUser($identifier, $attributes);

            if ($existing !== null) {
                return $existing;
            }

            return User::create($this->identityData($identifier, $attributes));
        });
    }

    /**
     * @param array<string, string> $attributes
     */
    private function findExistingUser(string $identifier, array $attributes): ?User
    {
        $loginCandidates = array_values(array_filter([
            $identifier,
            $attributes['tjloginrede'] ?? null,
            $attributes['tjloginua'] ?? null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
        $emailCandidates = array_values(array_filter([
            $attributes['tjgoogleemailprinc'] ?? null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
        $registrationCandidates = array_values(array_filter([
            $attributes['tjmatricula'] ?? null,
            $attributes['tjmatricularhestag'] ?? null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
        $cpfCandidates = $this->cpfCandidates($attributes['cn'] ?? '');

        return User::query()
            ->where(function (Builder $query) use ($loginCandidates, $emailCandidates, $registrationCandidates, $cpfCandidates): void {
                if ($loginCandidates !== []) {
                    $query->whereIn('login', $loginCandidates);
                }

                if ($emailCandidates !== []) {
                    $query->orWhereIn('email', $emailCandidates);
                }

                foreach ($registrationCandidates as $registration) {
                    $query->orWhere('matricula', $registration)
                        ->orWhere('numero_funcional', $registration);
                }

                if ($cpfCandidates !== []) {
                    $query->orWhereIn('cpf', $cpfCandidates);
                }
            })
            ->first();
    }

    /**
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    private function identityData(string $identifier, array $attributes): array
    {
        $login = $this->firstFilled([
            $attributes['tjloginrede'] ?? null,
            $attributes['tjloginua'] ?? null,
            $identifier,
        ]);
        $email = $this->firstFilled([
            filter_var($attributes['tjgoogleemailprinc'] ?? null, FILTER_VALIDATE_EMAIL)
                ? $attributes['tjgoogleemailprinc']
                : null,
            filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null,
        ]);
        $name = $this->firstFilled([
            $attributes['fullname'] ?? null,
            trim(($attributes['givenname'] ?? '') . ' ' . ($attributes['sn'] ?? '')),
            $login,
        ]);
        $cpf = $this->cpfCandidates($attributes['cn'] ?? '')[0] ?? null;

        return [
            'name' => $name,
            'login' => $login,
            'email' => $email ?? $this->provisionalEmail($identifier, $attributes),
            'password' => Str::random(64),
            'cpf' => $cpf,
            'matricula' => $attributes['tjmatricula'] ?? null,
            'numero_funcional' => $attributes['tjmatricularhestag'] ?? null,
        ];
    }

    /**
     * @param list<?string> $values
     */
    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * O schema atual exige e-mail, mas o LDAP pode não fornecê-lo. Este valor
     * temporário fica editável na tela de complemento e nunca é tratado como
     * e-mail confirmado.
     *
     * @param array<string, string> $attributes
     */
    private function provisionalEmail(string $identifier, array $attributes): string
    {
        $fingerprint = substr(hash('sha256', $identifier . '|' . ($attributes['cn'] ?? '')), 0, 24);

        return self::PROVISIONAL_EMAIL_PREFIX . $fingerprint . '@cadastro.local';
    }

    /**
     * @return list<string>
     */
    private function cpfCandidates(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (! is_string($digits) || strlen($digits) !== 11) {
            return [];
        }

        return [
            $digits,
            substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2),
        ];
    }
}
