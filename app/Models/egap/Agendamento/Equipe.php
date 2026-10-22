<?php

namespace App\Models\egap\Agendamento;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipe extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_age_equipe';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_user',
        'funcao',
        'id_pessoa',
        'contato',
        'disponivel',
        'ativo',
    ];

    public function idUserRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user', 'id');
    }
    public function idPessoaRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_pessoa', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->id_user = EgapSession::resolverAtual() ?? 111;
        });
    }
}
