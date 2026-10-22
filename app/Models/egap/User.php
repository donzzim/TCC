<?php

namespace App\Models\egap;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\egap\Cadastro\Setores;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const GUARD = 'egap';
    public const ROLE_USER = 'egap_user';
    public const ROLE_ADMIN = 'egap_admin';
    public const ROLE_SUPER_ADMIN = 'egap_super_admin';
    public const ROLE_EXTERNO = 'egap_externo';

    protected $table = 'users';

    protected string $guard_name = self::GUARD;

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
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function info(): HasOne
    {
        return $this->hasOne(UserInfo::class, 'user_id', 'id');
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(UserLotacao::class, 'user_id', 'id');
    }

    public function setores(): BelongsToMany
    {
        return $this->belongsToMany(Setores::class, 'user_lotacao', 'user_id', 'setor_id')
            ->withPivot('principal', 'atualizado_por')
            ->withTimestamps();
    }

    public function setorPrincipal(): BelongsToMany
    {
        return $this->setores()->wherePivot('principal', 1);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'egap'
            && ($this->can('egap_access') || $this->isAdminEgap());
    }

    public function isSuperAdminEgap(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdminEgap(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    public function acessaAreaExterna(): bool
    {
        return $this->isAdminEgap() || $this->isSuperAdminEgap() || $this->hasRole(self::ROLE_EXTERNO);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::disk('public')->url($this->avatar_url) : null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }
}
