<?php

namespace App\Models\egap\Agendamento;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recursos extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_age_recursos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_user',
        'condutor',
        'veiculo',
        'id_solicitacao',
        'observacao',
    ];

    public function idUserRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user');
    }

    public function condutorRef(): BelongsTo
    {
        return $this->belongsTo(Equipe::class, 'condutor', 'id_pessoa');
    }

    public function veiculoRef(): BelongsTo
    {
        return $this->belongsTo(Frota::class, 'veiculo');
    }

    public function idSolicitacaoRef(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class, 'id_solicitacao');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->id_user = EgapSession::resolverAtual() ?? 111;
        });
    }
}
