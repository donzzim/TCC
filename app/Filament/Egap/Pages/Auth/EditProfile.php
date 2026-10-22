<?php

namespace App\Filament\Egap\Pages\Auth;

use App\Models\egap\Admin\Lotacao;
use App\Models\egap\UserEgap;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conta')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto')
                            ->avatar()
                            ->disk('public')
                            ->directory('egap/images/avatars')
                            ->visibility('public')
                            ->columnSpanFull(),
                        $this->getNameFormComponent(),
                        $this->getLoginFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->columns(2),
                Section::make('Dados Pessoais')
                    ->schema([
                        TextInput::make('cpf')
                            ->label('CPF')
                            ->mask('999.999.999-99')
                            ->maxLength(14),
                        TextInput::make('telefone')
                            ->label('Telefone')
                            ->mask('(**)*****-****')
                            ->maxLength(15),
                        TextInput::make('matricula')
                            ->label('Matrícula')
                            ->maxLength(255),
                        TextInput::make('numero_funcional')
                            ->label('Número Funcional')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Informações Institucionais')
                    ->description('Dados sincronizados do sistema EGAP. Para corrigir, procure a Administração.')
                    ->schema($this->getInstitutionalFormComponents())
                    ->columns(2),
            ]);
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Login')
            ->disabled()
            ->dehydrated(false);
    }

    /**
     * @return array<int, Component>
     */
    protected function getInstitutionalFormComponents(): array
    {
        $data = $this->resolveInstitutionalData();

        return [
            Placeholder::make('ativo')
                ->label('Status da conta')
                ->content($this->getUser()->getAttribute('ativo') ? 'Ativo' : 'Inativo'),
            Placeholder::make('cargo')
                ->label('Cargo')
                ->content($data['cargo']),
            Placeholder::make('perfis_acesso')
                ->label('Perfis de acesso')
                ->content($data['perfis_acesso']),
            Placeholder::make('unidade_judiciaria')
                ->label('Unidade Judiciária')
                ->content($data['unidade_judiciaria']),
            Placeholder::make('setor')
                ->label('Setor')
                ->content($data['setor']),
            Placeholder::make('data_cadastro')
                ->label('Cadastrado em')
                ->content($data['data_cadastro']),
            Placeholder::make('ultimo_acesso')
                ->label('Último acesso')
                ->content($data['ultimo_acesso']),
            Placeholder::make('bloqueado_legado')
                ->label('Bloqueado no sistema legado')
                ->content($data['bloqueado_legado']),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function resolveInstitutionalData(): array
    {
        $userEgap = UserEgap::currentAuthenticated();

        $lotacao = $userEgap
            ?->lotacoes()
            ->with(['unidadeJudiciaria:id,setor', 'setorRef:id,setor'])
            ->first();

        return ProfileInstitutionalDataResolver::resolve(
            $userEgap,
            $userEgap?->infoUser,
            $lotacao instanceof Lotacao ? $lotacao : null,
            $this->getUser()->getRoleNames()->values()->all(),
        );
    }
}
