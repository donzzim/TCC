<?php

namespace Database\Seeders;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MobileApiTestUsersSeeder extends Seeder
{
    private const LOGIN = 'mobile.teste';

    private const PASSWORD = 'mobile123';

    private const CPF = '90000000001';

    /** Setor principal (unidade judiciária 766) e um segundo setor da mesma unidade. */
    private const SETOR_PRINCIPAL = 2296;

    private const SETOR_SECUNDARIO = 767;

    /** Usuários de versões anteriores do seeder, sem uso na API atual. */
    private const LOGINS_LEGADOS = ['mobile.local', 'mobile.egap.local', 'mobile.sem-vinculo'];

    public function run(): void
    {
        $this->removerUsuariosLegados();

        $user = User::query()->updateOrCreate(
            ['login' => self::LOGIN],
            [
                'name' => 'Mobile Teste',
                'email' => 'mobile.teste@egap.test',
                'cpf' => self::CPF,
                'telefone' => '(11) 99999-0001',
                'matricula' => 'MOB001',
                'numero_funcional' => 'MOB001',
                'ativo' => 1,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        $user->syncRoles([User::ROLE_USER]);

        $this->upsertLotacoes($user);
        $this->upsertInfoUser($this->upsertEgapUser($user));

        $this->command?->info('Usuário de teste da API mobile criado/atualizado.');
        $this->command?->line('Login: ' . self::LOGIN . ' / senha: ' . self::PASSWORD);
    }

    private function removerUsuariosLegados(): void
    {
        $legados = User::query()->whereIn('login', self::LOGINS_LEGADOS)->get();

        foreach ($legados as $legado) {
            UserLotacao::query()->where('user_id', $legado->getKey())->delete();
            $legado->tokens()->delete();
            $legado->syncRoles([]);
            $legado->delete();
        }

        UserEgap::query()->whereIn('username', ['mobile.local.egap', 'mobile.egap'])->get()
            ->each(function (UserEgap $egap): void {
                InfoUser::query()->where('usuario_id', $egap->getKey())->delete();
                $egap->delete();
            });
    }

    private function upsertLotacoes(User $user): void
    {
        foreach ([self::SETOR_PRINCIPAL => true, self::SETOR_SECUNDARIO => false] as $setorId => $principal) {
            UserLotacao::query()->updateOrCreate(
                ['user_id' => $user->getKey(), 'setor_id' => $setorId],
                ['principal' => $principal, 'atualizado_por' => $user->getKey()],
            );
        }
    }

    /**
     * O egap_id da sessão mobile vem de egap_mat_infousers (por CPF) -> usuário EGAP legado.
     */
    private function upsertEgapUser(User $user): UserEgap
    {
        $egap = UserEgap::query()->firstOrNew(['username' => self::LOGIN]);

        $egap->fill([
            'name' => $user->name,
            'email' => $user->email,
            'password' => Hash::make(self::PASSWORD),
            'block' => false,
            'sendEmail' => false,
            'registerDate' => $egap->registerDate ?? now(),
            'lastvisitDate' => now(),
            'activation' => '',
            'params' => '{}',
            'lastResetTime' => now(),
            'resetCount' => 0,
            'otpKey' => '',
            'otep' => '',
            'requireReset' => false,
        ]);
        $egap->save();

        return $egap;
    }

    private function upsertInfoUser(UserEgap $egap): void
    {
        InfoUser::query()->updateOrCreate(
            ['usuario_id' => $egap->getKey(), 'cpf' => self::CPF],
            ['matricula' => 'MOB001', 'cargo' => 'Teste API Mobile'],
        );
    }
}
