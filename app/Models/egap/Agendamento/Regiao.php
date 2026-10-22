<?php

namespace App\Models\egap\Agendamento;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regiao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_age_regiao';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'regiao',
        'unidade',
        'atualizado_por',
        'sigla',
    ];

    public function unidadeRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade', 'id');
    }

    public function atualizadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'regiao', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
