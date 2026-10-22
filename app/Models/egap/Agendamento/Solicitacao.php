<?php

namespace App\Models\egap\Agendamento;

use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitacao extends Model
{
    public const TIPO_VEICULOS = 1;

    public const SITUACAO_EM_ANALISE = 6;

    public const SITUACAO_AGENDADO = 8;

    public const SITUACAO_CONCLUIDO = 3;

    public const SITUACAO_CANCELADO = 4;

    public const SITUACAO_INVALIDADO = 5;

    protected $connection = 'emes';
    protected $table = 'egap_age_solicitacao';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'data_alteracao',
        'id_user',
        'tipo',
        'id_situacao',
        'id_solicitante',
        'setor_solicitante',
        'data_inicio',
        'hora_inicio',
        'data_termino',
        'hora_termino',
        'justificativa',
        'local_saida',
        'local_destino',
        'motivo_cancelamento',
        'motivo_edicao',
        'finalizar',
        'regiao',
        'unidade_solicitante',
        'anexo',
        'agendamento_pai',
    ];

    protected $casts = [
        'justificativa' => 'array',
    ];

    public function idUserRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user', 'id');
    }

    public function idSolicitanteRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_solicitante', 'id');
    }

    public function idSituacaoRef(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'id_situacao', 'id');
    }

    public function unidadeSolicitanteRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_solicitante', 'id');
    }

    public function materiaisRef(): HasMany
    {
        return $this->hasMany(Materiais::class, 'id_solicitacao', 'id');
    }

    public function recursosRef(): HasMany
    {
        return $this->hasMany(Recursos::class, 'id_solicitacao', 'id');
    }

    public function setorSolicitanteRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_solicitante', 'id');
    }

    public function regiaoRef(): BelongsTo
    {
        return $this->belongsTo(Regiao::class, 'regiao', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->data_alteracao = now();
            $model->id_user = EgapSession::resolverAtual() ?? 111;
        });
    }
}
