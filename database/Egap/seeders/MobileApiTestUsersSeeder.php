<?php

namespace Database\Seeders;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\UserEgap;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MobileApiTestUsersSeeder extends Seeder
{
    private const PASSWORD = 'mobile123';

    private const UNIDADE_JUDICIARIA = 766;

    private const SETOR = 2296;

    public function run(): void
    {
        $this->createLocalLinkedUser();
        $this->createEgapLinkedUser();
        $this->createLocalUserWithoutMobileLink();

        $this->command?->info('Usuários de teste da API mobile criados/atualizados.');
        $this->command?->line('Senha padrão: ' . self::PASSWORD);
        $this->command?->line('Login local com vínculo: mobile.local');
        $this->command?->line('Login EGAP com vínculo: mobile.egap');
        $this->command?->line('Login local sem vínculo mobile: mobile.sem-vínculo');
    }

    private function createLocalLinkedUser(): void
    {
        $cpf = '90000000001';

        $user = $this->upsertLocalUser([
            'login' => 'mobile.local',
            'name' => 'Mobile Local Teste',
            'email' => 'mobile.local@egap.test',
            'cpf' => $cpf,
            'matricula' => 'MOB001',
            'numero_funcional' => 'MOB001',
            'telefone' => '(11) 99999-0001',
        ]);

        $egapUser = $this->upsertEgapUser([
            'name' => $user->name,
            'username' => 'mobile.local.egap',
            'email' => 'mobile.local.egap@egap.test',
        ]);

        $this->upsertInfoUser($egapUser, $cpf, 'MOB001', 'Teste API Mobile Local');
        $this->upsertLotacao($egapUser);
    }

    private function createEgapLinkedUser(): void
    {
        $cpf = '90000000002';

        $this->upsertLocalUser([
            'login' => 'mobile.egap.local',
            'name' => 'Mobile EGAP Teste',
            'email' => 'mobile.egap.local@egap.test',
            'cpf' => $cpf,
            'matricula' => 'MOB002',
            'numero_funcional' => 'MOB002',
            'telefone' => '(11) 99999-0002',
        ]);

        $egapUser = $this->upsertEgapUser([
            'name' => 'Mobile EGAP Teste',
            'username' => 'mobile.egap',
            'email' => 'mobile.egap@egap.test',
        ]);

        $this->upsertInfoUser($egapUser, $cpf, 'MOB002', 'Teste API Mobile EGAP');
        $this->upsertLotacao($egapUser);
    }

    private function createLocalUserWithoutMobileLink(): void
    {
        $this->upsertLocalUser([
            'login' => 'mobile.sem-vinculo',
            'name' => 'Mobile Sem Vinculo Teste',
            'email' => 'mobile.sem-vinculo@egap.test',
            'cpf' => '90000000003',
            'matricula' => 'MOB003',
            'numero_funcional' => 'MOB003',
            'telefone' => '(11) 99999-0003',
        ]);
    }

    private function upsertLocalUser(array $attributes): User
    {
        return User::query()->updateOrCreate(
            ['login' => $attributes['login']],
            [
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'cpf' => $attributes['cpf'],
                'telefone' => $attributes['telefone'],
                'matricula' => $attributes['matricula'],
                'numero_funcional' => $attributes['numero_funcional'],
                'ativo' => 1,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ],
        );
    }

    private function upsertEgapUser(array $attributes): UserEgap
    {
        $user = UserEgap::query()->firstOrNew([
            'username' => $attributes['username'],
        ]);

        $user->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => Hash::make(self::PASSWORD),
            'block' => false,
            'sendEmail' => false,
            'registerDate' => now(),
            'lastvisitDate' => now(),
            'activation' => '',
            'params' => '{}',
            'lastResetTime' => now(),
            'resetCount' => 0,
            'otpKey' => '',
            'otep' => '',
            'requireReset' => false,
        ]);

        $user->save();

        return $user;
    }

    private function upsertInfoUser(UserEgap $user, string $cpf, string $matricula, string $cargo): void
    {
        InfoUser::query()->updateOrCreate(
            [
                'usuario_id' => $user->getKey(),
                'cpf' => $cpf,
            ],
            [
                'date_time' => now(),
                'matricula' => $matricula,
                'cargo' => $cargo,
            ],
        );
    }

    private function upsertLotacao(UserEgap $user): void
    {
        Lotacao::withoutEvents(fn() => Lotacao::query()->updateOrCreate(
            [
                'id_user' => $user->getKey(),
                'unidade_judiciaria' => self::UNIDADE_JUDICIARIA,
                'setor' => self::SETOR,
            ],
            [
                'date_time' => now(),
                'usuario' => $user->getKey(),
            ],
        ));
    }
}
