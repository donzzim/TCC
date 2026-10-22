<?php

namespace App\Auth;

use App\Services\Auth\TjesLdapService;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Provider dos painéis EMES: mantém a autenticação local e acrescenta TJES.
 *
 * O LDAP não cria nem altera registros. Para entrar no Filament, a conta TJES
 * precisa corresponder a um usuário EMES já cadastrado por login, e-mail,
 * CPF, matrícula ou número funcional.
 */
class HybridUserProvider extends EloquentUserProvider
{
    public function __construct(
        Hasher $hasher,
        string $model,
        private readonly TjesLdapService $ldap,
    ) {
        parent::__construct($hasher, $model);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $identifier = $this->identifierFromCredentials($credentials);

        if ($identifier === null) {
            Log::warning('auth.hybrid.identifier_missing', $this->logContext($credentials));

            return null;
        }

        session()->forget(TjesLdapService::SESSION_PENDING_REGISTRATION_KEY);

        $context = $this->logContext($credentials, $identifier);
        Log::info('auth.hybrid.lookup_started', $context);

        $user = $this->findLocalUser($identifier);

        if ($user !== null) {
            Log::info('auth.hybrid.local_user_found', $context + [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return $user;
        }

        Log::info('auth.hybrid.local_user_not_found', $context);
        Log::info('auth.hybrid.ldap_identity_lookup_started', $context);

        $ldapUser = $this->ldap->find($identifier);

        if ($ldapUser === null) {
            Log::info('auth.hybrid.ldap_identity_not_found', $context);

            return null;
        }

        Log::info('auth.hybrid.ldap_identity_found', $context + [
            'ldap_attribute_count' => count($ldapUser['attributes']),
        ]);

        $user = $this->findLocalUserByLdapAttributes($ldapUser['attributes']);

        if ($user === null) {
            if (($credentials['_panel'] ?? null) === 'app') {
                session()->put(TjesLdapService::SESSION_PENDING_REGISTRATION_KEY, [
                    'identifier' => $identifier,
                    'attributes' => $ldapUser['attributes'],
                ]);

                Log::info('auth.hybrid.ldap_identity_pending_app_registration', $context);
            } else {
                Log::warning('auth.hybrid.ldap_identity_without_local_user', $context);
            }

            return null;
        }

        Log::info('auth.hybrid.local_user_matched_by_ldap', $context + [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        return $user;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        if (! is_string($password) || $password === '') {
            Log::warning('auth.hybrid.password_missing', [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return false;
        }

        $identifier = $this->identifierFromCredentials($credentials);
        $context = $this->logContext($credentials, $identifier);

        $storedPassword = $user->getAuthPassword();

        Log::info('auth.hybrid.local_password_validation_started', $context + [
            'user_id' => $user->getAuthIdentifier(),
            'has_local_password' => is_string($storedPassword) && $storedPassword !== '',
        ]);

        if (is_string($storedPassword) && $storedPassword !== '' && $this->hasher->check($password, $storedPassword)) {
            session()->forget([
                TjesLdapService::SESSION_AUTHENTICATED_KEY,
                TjesLdapService::SESSION_ATTRIBUTES_KEY,
            ]);

            Log::info('auth.hybrid.local_password_succeeded', $context + [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return true;
        }

        if ($identifier === null) {
            Log::warning('auth.hybrid.ldap_fallback_skipped_identifier_missing', [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return false;
        }

        Log::info('auth.hybrid.local_password_failed_ldap_fallback_started', $context + [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        if (! $this->ldap->isEnabled()) {
            Log::warning('auth.hybrid.ldap_fallback_unavailable', $context + [
                'user_id' => $user->getAuthIdentifier(),
            ]);
            $this->clearLdapSession();

            return false;
        }

        $ldapUser = $this->ldap->authenticateUser($identifier, $password);

        if ($ldapUser === null) {
            Log::warning('auth.hybrid.ldap_password_failed', $context + [
                'user_id' => $user->getAuthIdentifier(),
            ]);
            $this->clearLdapSession();

            return false;
        }

        session()->put(TjesLdapService::SESSION_AUTHENTICATED_KEY, true);
        session()->put(TjesLdapService::SESSION_ATTRIBUTES_KEY, $ldapUser['attributes']);

        Log::info('auth.hybrid.ldap_password_succeeded', $context + [
            'user_id' => $user->getAuthIdentifier(),
            'ldap_attribute_count' => count($ldapUser['attributes']),
        ]);

        return true;
    }

    private function identifierFromCredentials(array $credentials): ?string
    {
        foreach (['login', 'email', 'cpf', 'matricula', 'numero_funcional', 'username'] as $key) {
            $value = $credentials[$key] ?? null;

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    private function logContext(array $credentials, ?string $identifier = null): array
    {
        $context = [
            'identifier_type' => $this->identifierType($credentials),
        ];

        if ($identifier !== null) {
            $context['identifier_fingerprint'] = substr(hash_hmac(
                'sha256',
                mb_strtolower(trim($identifier)),
                (string) config('app.key'),
            ), 0, 16);
        }

        if (app()->bound('request')) {
            $request = request();
            $context['request_path'] = $request->path();
            $context['route'] = $request->route()?->getName();
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function identifierType(array $credentials): string
    {
        foreach (['login', 'email', 'cpf', 'matricula', 'numero_funcional', 'username'] as $key) {
            if (isset($credentials[$key]) && is_scalar($credentials[$key]) && trim((string) $credentials[$key]) !== '') {
                return $key;
            }
        }

        return 'unknown';
    }

    private function clearLdapSession(): void
    {
        session()->forget([
            TjesLdapService::SESSION_AUTHENTICATED_KEY,
            TjesLdapService::SESSION_ATTRIBUTES_KEY,
        ]);
    }

    private function findLocalUser(string $identifier): ?Authenticatable
    {
        $model = $this->createModel();
        $cpfCandidates = $this->cpfCandidates($identifier);

        return $model->newQuery()
            ->where(function (Builder $query) use ($identifier, $cpfCandidates): void {
                $query->where('login', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('matricula', $identifier)
                    ->orWhere('numero_funcional', $identifier);

                if ($cpfCandidates !== []) {
                    $query->orWhereIn('cpf', $cpfCandidates);
                }
            })
            ->first();
    }

    /**
     * @param array<string, string> $attributes
     */
    private function findLocalUserByLdapAttributes(array $attributes): ?Authenticatable
    {
        $loginCandidates = array_values(array_filter([
            $attributes['tjloginrede'] ?? null,
            $attributes['tjloginua'] ?? null,
        ]));
        $email = $attributes['tjgoogleemailprinc'] ?? null;
        $registrationCandidates = array_values(array_filter([
            $attributes['tjmatricula'] ?? null,
            $attributes['tjmatricularhestag'] ?? null,
        ]));
        $cpfCandidates = $this->cpfCandidates($attributes['cn'] ?? '');

        if ($loginCandidates === [] && $email === null && $registrationCandidates === [] && $cpfCandidates === []) {
            return null;
        }

        return $this->createModel()->newQuery()
            ->where(function (Builder $query) use ($loginCandidates, $email, $registrationCandidates, $cpfCandidates): void {
                if ($loginCandidates !== []) {
                    $query->whereIn('login', $loginCandidates);
                }

                if ($email !== null) {
                    $query->orWhere('email', $email);
                }

                if ($registrationCandidates !== []) {
                    foreach ($registrationCandidates as $registration) {
                        $query->orWhere('matricula', $registration)
                            ->orWhere('numero_funcional', $registration);
                    }
                }

                if ($cpfCandidates !== []) {
                    $query->orWhereIn('cpf', $cpfCandidates);
                }
            })
            ->first();
    }

    /**
     * @return list<string>
     */
    private function cpfCandidates(string $value): array
    {
        $digits = preg_replace('/\\D+/', '', $value);

        if (! is_string($digits) || $digits === '' || strlen($digits) < 8) {
            return [];
        }

        $candidates = [$digits];

        if (strlen($digits) === 11) {
            $candidates[] = substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
        }

        return array_values(array_unique($candidates));
    }
}
