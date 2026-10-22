<?php

namespace App\Models\egap;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserEgap extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_jos_users';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'block',
        'sendEmail',
        'registerDate',
        'lastvisitDate',
        'activation',
        'params',
        'lastResetTime',
        'resetCount',
        'otpKey',
        'otep',
        'requireReset',
    ];

    protected $casts = [
        'block' => 'boolean',
        'sendEmail' => 'boolean',
        'requireReset' => 'boolean',
        'registerDate' => 'datetime',
        'lastvisitDate' => 'datetime',
        'lastResetTime' => 'datetime',
    ];

    public function infoUser(): HasOne
    {
        return $this->hasOne(InfoUser::class, 'usuario_id', 'id');
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(Lotacao::class, 'id_user', 'id')
            ->orderByDesc('date_time')
            ->orderByDesc('id');
    }

    /**
     * Resolve o registro jos_users correspondente ao usuário autenticado no painel
     * EGAP (App\Models\egap\User, guard `egap`), casando pelo login/username.
     */
    public static function currentAuthenticated(): ?self
    {
        $user = auth(User::GUARD)->user();

        if ($user instanceof self) {
            return $user;
        }

        $login = $user?->login ?? $user?->username ?? null;

        if (blank($login)) {
            return null;
        }

        return static::query()->where('username', $login)->first();
    }
}
