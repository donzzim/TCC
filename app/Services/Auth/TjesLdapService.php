<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Log;

/**
 * Integração mínima com o diretório LDAP do TJES.
 *
 * O diretório é consultado com uma conta técnica e a senha do usuário é
 * validada fazendo bind com o DN encontrado. Nenhuma senha LDAP é persistida.
 */
class TjesLdapService
{
    public const SESSION_AUTHENTICATED_KEY = 'auth.tjes_ldap.authenticated';
    public const SESSION_ATTRIBUTES_KEY = 'auth.tjes_ldap.attributes';
    public const SESSION_PENDING_REGISTRATION_KEY = 'auth.tjes_ldap.pending_registration';

    /**
     * Localiza um usuário pelos identificadores expostos pelo TJES.
     *
     * @return array{dn: string, attributes: array<string, string>}|null
     */
    public function find(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            Log::warning('auth.tjes_ldap.lookup_empty_identifier');

            return null;
        }

        $ldap = $this->connectAsServiceAccount();

        if ($ldap === null) {
            Log::warning('auth.tjes_ldap.lookup_unavailable', [
                'enabled' => (bool) config('services.tjes_ldap.enabled', false),
                'extension_loaded' => function_exists('ldap_connect'),
            ]);

            return null;
        }

        try {
            $escaped = $this->escapeFilterValues($identifier);
            $filters = [
                "(tjloginrede={$escaped})",
                "(tjloginua={$escaped})",
                "(tjmatricula={$escaped})",
                "(tjmatricularhestag={$escaped})",
                "(tjgoogleemailprinc={$escaped})",
                "(cn={$escaped})",
            ];

            // Também permite CPF digitado com pontuação no login do Filament.
            $digits = preg_replace('/\\D+/', '', $identifier);
            if ($digits !== '' && $digits !== $identifier) {
                $digits = ldap_escape($digits, '', LDAP_ESCAPE_FILTER);
                $filters[] = "(cn={$digits})";
                $filters[] = "(tjmatricula={$digits})";
                $filters[] = "(tjmatricularhestag={$digits})";
            }

            $result = @ldap_search(
                $ldap,
                (string) config('services.tjes_ldap.base_dn'),
                '(|' . implode('', $filters) . ')',
                $this->attributesToRequest(),
            );

            if ($result === false) {
                Log::warning('auth.tjes_ldap.search_failed', [
                    'error_code' => @ldap_errno($ldap),
                ]);

                return null;
            }

            $entries = @ldap_get_entries($ldap, $result);

            if (! is_array($entries) || ($entries['count'] ?? 0) < 1) {
                Log::info('auth.tjes_ldap.user_not_found');

                return null;
            }

            $entry = $entries[0];
            // ldap_get_entries() já fornece o DN no campo "dn". O array
            // retornado por ela não pode ser passado para ldap_get_dn().
            $dn = $entry['dn'] ?? null;

            if (! is_string($dn) || $dn === '') {
                Log::warning('auth.tjes_ldap.user_dn_unavailable');

                return null;
            }

            return [
                'dn' => $dn,
                'attributes' => $this->normalizeAttributes($entry),
            ];
        } finally {
            @ldap_unbind($ldap);
        }
    }

    public function authenticate(string $identifier, string $password): bool
    {
        return $this->authenticateUser($identifier, $password) !== null;
    }

    /**
     * Valida a senha e devolve os atributos da conta, sem expor a senha.
     *
     * @return array{dn: string, attributes: array<string, string>}|null
     */
    public function authenticateUser(string $identifier, string $password): ?array
    {
        if ($password === '' || ! $this->isEnabled()) {
            Log::warning('auth.tjes_ldap.authentication_skipped', [
                'password_present' => $password !== '',
                'enabled' => $this->isEnabled(),
            ]);

            return null;
        }

        $user = $this->find($identifier);

        if ($user === null || ! $this->isAccountAllowed($user['attributes'])) {
            Log::warning('auth.tjes_ldap.account_not_allowed_or_not_found', [
                'account_found' => $user !== null,
            ]);

            return null;
        }

        $ldap = $this->connect();

        if ($ldap === null) {
            return null;
        }

        try {
            $authenticated = @ldap_bind($ldap, $user['dn'], $password);

            Log::info($authenticated
                ? 'auth.tjes_ldap.password_succeeded'
                : 'auth.tjes_ldap.password_failed', [
                    'error_code' => $authenticated ? null : @ldap_errno($ldap),
                    'error_message' => $authenticated ? null : @ldap_error($ldap),
                ]);

            return $authenticated ? $user : null;
        } finally {
            @ldap_unbind($ldap);
        }
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.tjes_ldap.enabled', false)
            && function_exists('ldap_connect');
    }

    /**
     * @return resource|object|null
     */
    private function connectAsServiceAccount(): mixed
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $ldap = $this->connect();

        if ($ldap === null) {
            return null;
        }

        $dn = trim((string) config('services.tjes_ldap.service_dn'));
        $password = (string) config('services.tjes_ldap.service_password');

        if ($dn === '' || $password === '') {
            Log::error('auth.tjes_ldap.service_credentials_missing', [
                'service_dn_present' => $dn !== '',
                'service_password_present' => $password !== '',
            ]);
            @ldap_unbind($ldap);

            return null;
        }

        if (! @ldap_bind($ldap, $dn, $password)) {
            Log::error('auth.tjes_ldap.service_bind_failed', [
                'error_code' => @ldap_errno($ldap),
                'error_message' => @ldap_error($ldap),
            ]);
            @ldap_unbind($ldap);

            return null;
        }

        return $ldap;
    }

    /**
     * @return resource|object|null
     */
    private function connect(): mixed
    {
        if (! function_exists('ldap_connect')) {
            return null;
        }

        $scheme = config('services.tjes_ldap.scheme', 'ldap');
        $host = config('services.tjes_ldap.host');
        $port = (int) config('services.tjes_ldap.port', 389);

        if (! is_string($host) || trim($host) === '') {
            Log::error('auth.tjes_ldap.host_missing');

            return null;
        }

        $ldap = @ldap_connect(sprintf('%s://%s', $scheme, trim($host)), $port);

        if ($ldap === false) {
            Log::error('auth.tjes_ldap.connection_failed', [
                'scheme' => $scheme,
                'host' => trim($host),
                'port' => $port,
            ]);

            return null;
        }

        @ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        if (defined('LDAP_OPT_NETWORK_TIMEOUT')) {
            @ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, (int) config('services.tjes_ldap.timeout', 5));
        }

        if ((bool) config('services.tjes_ldap.start_tls', false)) {
            if (! @ldap_start_tls($ldap)) {
                Log::error('auth.tjes_ldap.start_tls_failed', [
                    'error_code' => @ldap_errno($ldap),
                    'error_message' => @ldap_error($ldap),
                ]);
                @ldap_unbind($ldap);

                return null;
            }
        }

        return $ldap;
    }

    /**
     * @return list<string>
     */
    private function attributesToRequest(): array
    {
        return [
            'tjloginrede',
            'tjloginua',
            'tjmatricula',
            'tjmatricularhestag',
            'tjgoogleemailprinc',
            'cn',
            'fullname',
            'givenname',
            'sn',
            'tjdescricaolotacao',
            'tjdescricaocomarca',
            'tjtipousuario',
            'tjtipousuariorhestag',
            'tjstatus',
            'tjstatusrhestag',
            'tjorgaoprinc',
            'tjorgaoprincrhestag',
            'tjcargorhestag',
            'title',
            'tjacessoportal',
            'tjacessorede',
        ];
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, string>
     */
    private function normalizeAttributes(array $entry): array
    {
        $attributes = [];

        foreach ($this->attributesToRequest() as $attribute) {
            $value = $entry[$attribute] ?? null;

            if (is_array($value) && isset($value[0])) {
                $value = $value[0];
            }

            if (is_scalar($value)) {
                $attributes[strtolower($attribute)] = trim((string) $value);
            }
        }

        return $attributes;
    }

    private function escapeFilterValues(string $identifier): string
    {
        return ldap_escape($identifier, '', LDAP_ESCAPE_FILTER);
    }

    /**
     * Impede autenticação de contas inativas ou sem acesso ao portal quando
     * o diretório fornece esses atributos.
     *
     * @param array<string, string> $attributes
     */
    private function isAccountAllowed(array $attributes): bool
    {
        foreach (['tjstatus', 'tjstatusrhestag'] as $statusAttribute) {
            if (isset($attributes[$statusAttribute]) && strtoupper($attributes[$statusAttribute]) !== 'A') {
                return false;
            }
        }

        if ((bool) config('services.tjes_ldap.require_portal_access', true)
            && isset($attributes['tjacessoportal'])
            && ! in_array(strtoupper($attributes['tjacessoportal']), ['TRUE', '1', 'SIM', 'S'], true)) {
            return false;
        }

        return true;
    }
}
