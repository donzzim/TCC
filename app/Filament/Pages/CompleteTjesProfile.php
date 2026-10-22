<?php

namespace App\Filament\Pages;

use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Perfil;
use App\Models\Setores;
use App\Models\User;
use App\Services\Auth\TjesLdapService;
use App\Services\Auth\TjesProfileCompletionService;
use App\Services\Auth\TjesUserProvisioningService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class CompleteTjesProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'completar-cadastro';
    protected static ?string $title = 'Complete seu cadastro';
    protected static ?string $navigationLabel = 'Complete seu cadastro';
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.complete-tjes-profile';

    public ?array $data = [];

    public function mount(TjesProfileCompletionService $completion): void
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        if (! $completion->needsCompletion($user)) {
            redirect()->to(Filament::getUrl());

            return;
        }

        $this->form->fill($completion->initialData($user));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificacao')
                    ->description('Preencha os dados que ainda faltam no seu cadastro.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome completo')
                            ->required()
                            ->disabled(fn (): bool => $this->identityFieldIsLocked('name'))
                            ->dehydrated(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->disabled(fn (): bool => $this->identityFieldIsLocked('email'))
                            ->dehydrated(),
                        TextInput::make('cpf')
                            ->label('CPF')
                            ->mask('999.999.999-99')
                            ->required()
                            ->disabled(fn (): bool => $this->identityFieldIsLocked('cpf'))
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Section::make('Vinculos')
                    ->schema([
                        Select::make('tipopessoa_id')
                            ->label('Vínculo / público-alvo')
                            ->options(fn (): array => TipoPublico::query()
                                ->orderBy('descricao')
                                ->pluck('descricao', 'id')
                                ->all())
                            ->required()
                            ->searchable(),
                        Select::make('setor_id')
                            ->label('Setor principal')
                            ->options(fn (): array => Setores::query()
                                ->orderBy('setor')
                                ->pluck('setor', 'id')
                                ->all())
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Dados complementares')
                    ->schema([
                        TextInput::make('telefone')->label('Telefone')->maxLength(30),
                        TextInput::make('cargo')->label('Cargo/Função')->maxLength(255),
                        Textarea::make('setor_orgao')->label('Vínculo empregatício / órgão / setor')->rows(3),
                        Select::make('sexo')->label('Sexo')->options(fn (): array => TipoSexo::query()->pluck('descricao', 'id')->all())->searchable(),
                        Select::make('genero')->label('Gênero')->options(fn (): array => TipoGenero::query()->pluck('descricao', 'id')->all())->searchable(),
                        Select::make('raca')->label('Cor/Raça')->options(fn (): array => TipoRaca::query()->pluck('descricao', 'id')->all())->searchable(),
                    ])
                    ->columns(2),

                Section::make('Pessoa com deficiência')
                    ->schema([
                        Toggle::make('deficiente')->label('Possuo deficiência')->live(),
                        Repeater::make('pcd')
                            ->label('Deficiências')
                            ->schema([
                                Select::make('tipo')
                                    ->label('Tipo')
                                    ->options(fn (): array => TipoDeficiencia::query()->pluck('descricao', 'id')->all())
                                    ->required(),
                                Textarea::make('descricao_necessidade')
                                    ->label('Descrição / necessidades')
                                    ->required(),
                            ])
                            ->hidden(fn (Get $get): bool => ! $get('deficiente'))
                            ->required(fn (Get $get): bool => (bool) $get('deficiente')),
                    ]),

                Checkbox::make('concordo')
                    ->label('Confirmo que os dados informados estão corretos e autorizo seu uso pela EMES.')
                    ->accepted(),
            ])
            ->statePath('data');
    }

    public function salvar(): void
    {
        $data = $this->form->getState();
        $user = Filament::auth()->user();

        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($user, $data): void {
            $identity = [];

            foreach (['name', 'email'] as $field) {
                $isProvisionalEmail = $field === 'email'
                    && str_starts_with((string) $user->email, TjesUserProvisioningService::PROVISIONAL_EMAIL_PREFIX);

                if ((blank($user->{$field}) || $isProvisionalEmail) && filled($data[$field] ?? null)) {
                    $identity[$field] = $data[$field];
                }
            }

            if (blank($user->cpf) && filled($data['cpf'] ?? null)) {
                $identity['cpf'] = preg_replace('/\D+/', '', (string) $data['cpf']);
            }

            if ($identity !== []) {
                $user->update($identity);
            }

            $profileData = [
                'cargo' => $data['cargo'] ?? null,
                'setor_orgao' => $data['setor_orgao'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'genero' => $data['genero'] ?? null,
                'raca' => $data['raca'] ?? null,
                'tipopessoa_id' => $data['tipopessoa_id'],
                'pcd' => ($data['deficiente'] ?? false) ? ($data['pcd'] ?? []) : null,
            ];

            if (filled($data['telefone'] ?? null)) {
                $profileData['contato'] = ['telefone' => $data['telefone']];
            }

            Perfil::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                $profileData,
            );

            $user->lotacao()->updateOrCreate(
                ['user_id' => $user->getKey(), 'principal' => true],
                [
                    'setor_id' => $data['setor_id'],
                    'atualizado_por' => $user->getKey(),
                    'principal' => true,
                ],
            );
        });

        session()->forget([
            TjesLdapService::SESSION_AUTHENTICATED_KEY,
            TjesLdapService::SESSION_ATTRIBUTES_KEY,
        ]);

        Notification::make()->title('Cadastro concluído com sucesso.')->success()->send();

        $this->redirect(Filament::getUrl());
    }

    private function identityFieldIsLocked(string $field): bool
    {
        $user = Filament::auth()->user();
        $attributes = session(TjesLdapService::SESSION_ATTRIBUTES_KEY, []);

        if (! $user instanceof User) {
            return false;
        }

        if (filled($user->{$field}) && ! ($field === 'email' && app(TjesProfileCompletionService::class)->isProvisionalEmail($user->email))) {
            return true;
        }

        return match ($field) {
            'name' => filled($attributes['fullname'] ?? null),
            'email' => filled($attributes['tjgoogleemailprinc'] ?? null),
            'cpf' => strlen((string) preg_replace('/\D+/', '', (string) ($attributes['cn'] ?? ''))) === 11,
            default => false,
        };
    }
}
