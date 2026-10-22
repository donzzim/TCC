<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Setores;
use Illuminate\Support\Str;

class TjesProfileCompletionService
{
    public function needsCompletion(User $user): bool
    {
        $profile = $user->perfil;

        return blank($user->name)
            || blank($user->cpf)
            || blank($user->email)
            || $profile === null
            || blank($profile->tipopessoa_id)
            || ! $user->setorPrincipal()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function initialData(User $user): array
    {
        $profile = $user->perfil;
        $attributes = session(TjesLdapService::SESSION_ATTRIBUTES_KEY, []);
        $sectorId = $user->setorPrincipal()->value('setores.id')
            ?? $this->resolveSectorId($attributes);
        $ldapCpf = preg_replace('/\D+/', '', (string) ($attributes['cn'] ?? ''));
        $ldapCpf = strlen($ldapCpf) === 11 ? $ldapCpf : null;

        return [
            'name' => $user->name ?: ($attributes['fullname'] ?? null),
            'email' => $this->isProvisionalEmail($user->email)
                ? ($attributes['tjgoogleemailprinc'] ?? null)
                : ($user->email ?: ($attributes['tjgoogleemailprinc'] ?? null)),
            'cpf' => $user->cpf ?: $ldapCpf,
            'telefone' => $user->telefone,
            'cargo' => $profile?->cargo ?: ($attributes['tjcargorhestag'] ?? $attributes['title'] ?? null),
            'setor_orgao' => $profile?->setor_orgao ?: ($attributes['tjdescricaolotacao'] ?? null),
            'sexo' => $profile?->sexo,
            'genero' => $profile?->genero,
            'raca' => $profile?->raca,
            'tipopessoa_id' => $profile?->tipopessoa_id ?: $this->inferPublicoAlvo($attributes),
            'setor_id' => $sectorId,
            'deficiente' => is_array($profile?->pcd) && count($profile->pcd) > 0,
            'pcd' => $profile?->pcd ?? [],
            'concordo' => false,
        ];
    }

    public function isProvisionalEmail(?string $email): bool
    {
        return is_string($email)
            && str_starts_with($email, TjesUserProvisioningService::PROVISIONAL_EMAIL_PREFIX)
            && str_ends_with($email, '@cadastro.local');
    }

    public function resolveSectorId(array $attributes): ?int
    {
        $code = trim((string) ($attributes['tjorgaoprinc'] ?? $attributes['tjorgaoprincrhestag'] ?? ''));

        if ($code === '') {
            return null;
        }

        $sector = Setores::query()
            ->whereKey($code)
            ->orWhere('cd_orgao', $code)
            ->first();

        return $sector?->getKey() !== null ? (int) $sector->getKey() : null;
    }

    private function inferPublicoAlvo(array $attributes): ?int
    {
        $text = Str::upper(trim(implode(' ', array_filter([
            $attributes['tjtipousuario'] ?? null,
            $attributes['tjtipousuariorhestag'] ?? null,
            $attributes['tjcargorhestag'] ?? null,
            $attributes['title'] ?? null,
        ]))));

        if (Str::contains($text, ['ESTAGI'])) {
            return 2102;
        }

        return null;
    }
}
