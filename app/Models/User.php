<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Administrativo\RegistroFuncional;
use App\Models\Contratacao\Processo;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\Instrutores;
use App\Services\MoodleService;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/** @property string|null $name */
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens, HasFactory, HasPanelShield, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'login',
        'email',
        'password',
        'avatar_url',
        'cpf',
        'telefone',
        'moodle_id',
        'matricula',
        'numero_funcional',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin' && ($this->hasRole('panel_user') || $this->hasRole('super_admin'))) {
            return true;
        }
        if ($panel->getId() === 'app') {
            return true;
        }

        return false;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatar = trim((string) $this->avatar_url);

        if ($avatar === '') {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://') || str_starts_with($avatar, '/')) {
            return $avatar;
        }

        return Storage::disk('public')->exists($avatar)
            ? Storage::disk('public')->url($avatar)
            : null;
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(CursoFrequencias::class, 'aluno_id', 'id');
    }

    public function atividades(): HasMany
    {
        return $this->HasMany(CursoAtividades::class, 'cursoatividade_id', 'id');
    }

    public function alunoCurso(): HasMany
    {
        return $this->HasMany(CursoAlunos::class, 'aluno_id', 'id');
    }

    public function processos(): BelongsToMany
    {
        return $this->belongsToMany(Processo::class, 'adm_gestores', 'processo_id', 'gestor_id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFuncional::class, 'pessoa_id', 'id');
    }

    public function lotacao(): HasMany
    {
        return $this->hasMany(Lotacao::class, 'user_id', 'id');
    }

    public function setorPrincipal()
    {
        return $this->setores()->wherePivot('principal', 1);
    }

    public function setores(): BelongsToMany
    {
        return $this->belongsToMany(Setores::class, 'user_lotacao', 'user_id', 'setor_id')
            ->using(Lotacao::class)
            ->withPivot('user_id', 'setor_id', 'principal', 'atualizado_por', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    public function isInstrutor(): bool
    {
        return Instrutores::where('instrutor_id', '=', $this->id)->count();
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Cursos::class, 'esc_instrutores', 'instrutor_id', 'curso_id')
            ->using(Instrutores::class)
            ->withPivot('curso_id', 'instrutor_id', 'atualizado_por', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    public function instrutores(): HasMany
    {
        return $this->hasMany(Instrutores::class, 'instrutor_id', 'id');
    }

    public function instrutorProcessos(): HasMany
    {
        return $this->hasMany(Processo::class, 'instrutor_id', 'id');
    }

    public function cursosComoInstrutor()
    {
        return $this->belongsToMany(Cursos::class, 'esc_instrutores', 'instrutor_id', 'curso_id');
    }

    public function cursosComoAluno()
    {
        return $this->belongsToMany(Cursos::class, 'esc_cursoalunos', 'aluno_id', 'curso_id');
    }

    public function atividadesComoAluno()
    {
        return $this->belongsToMany(CursoAtividades::class, 'esc_cursoalunos', 'aluno_id', 'curso_id');
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(CursoCertificados::class, 'pessoa_id', 'id');
    }

    public function perfil(): HasOne
    {
        return $this->hasOne(Perfil::class, 'user_id', 'id');
    }

    public function recadastro(): HasOne
    {
        return $this->hasOne(Recadastro::class, 'cpf', 'cpf');
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! empty($user->cpf)) {
                $user->cpf = preg_replace('/[^0-9]/', '', $user->cpf);
            }
            if (! empty($user->telefone)) {
                $user->telefone = preg_replace('/[^0-9]/', '', $user->telefone);
            }
        });

        static::created(function (User $user) {
            // `portal_user` só existe para a guard `web` — quando o usuário é
            // criado dentro de uma request dos painéis `app`/`egap` (guard
            // `pessoa`), a resolução automática de guard do Spatie quebra aqui.
            $user->removeRole('panel_user');
            $user->assignRole(Role::findByName('portal_user', 'web'));
            app(MoodleService::class)->syncUsuario($user);
        });

        static::updating(function (User $user) {
            if (! empty($user->cpf)) {
                $user->cpf = preg_replace('/[^0-9]/', '', $user->cpf);
            }
            if (! empty($user->telefone)) {
                $user->telefone = preg_replace('/[^0-9]/', '', $user->telefone);
            }
        });

        static::updated(function (User $user) {
            if ($user->ativo == 0 && $user->moodle_id) {
                app(MoodleService::class)->excluirUsuario($user->moodle_id);
            } else {
                app(MoodleService::class)->syncUsuario($user);
            }
        });

        static::deleted(function (User $user) {
            if ($user->moodle_id) {
                app(MoodleService::class)->excluirUsuario($user->moodle_id);
            }
        });
        static::deleting(function (User $user) {
            Notification::make()
                ->title('A exclusão de usuários é restrita.')
                ->send();

            return false;

            if ($user->moodle_id) {
                app(MoodleService::class)->excluirUsuario($user->moodle_id);
            }
        });
    }
}
