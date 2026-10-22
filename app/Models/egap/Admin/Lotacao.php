<?php

namespace App\Models\egap\Admin;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lotacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_lotacao';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'usuario',
        'id_user',
        'unidade_judiciaria',
        'setor',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user', 'id');
    }

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario', 'id');
    }

    public function unidadeJudiciaria(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_judiciaria', 'id');
    }

    public function setorRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->usuario = EgapSession::resolverAtual() ?? 111;
        });
    }

    protected static function resolveAuthenticatedEgapUserId(): ?int
    {
        $user = Filament::auth()->user()
            ?? auth('pessoa')->user()
            ?? auth()->user();

        if ($user instanceof UserEgap) {
            return $user->getKey();
        }

        $login = $user?->login ?? $user?->username ?? null;

        if (blank($login)) {
            return null;
        }

        return UserEgap::query()
            ->where('username', $login)
            ->value('id');
    }
}
