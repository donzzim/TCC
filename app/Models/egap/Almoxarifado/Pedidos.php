<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedidos extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_ped_pedidos';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'Solicitante',
        'UnidadeJudiciaria',
        'Setor',
        'Observacao',
        'DataTermino',
        'ResponsavelAtendimento',
        'idSituacao',
        'num_protocolo',
        'arquivo',
        'justificativa',
        'setor_responsavel',
        'ComplementoSetor',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'DataTermino' => 'datetime',
    ];

    public function termo(): HasOne
    {
        return $this->hasOne(Termo::class, 'pedido_no', 'id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class, 'idPedido', 'id');
    }

    public function itensPedido(): HasMany
    {
        return $this->itens();
    }

    public function fases(): HasMany
    {
        return $this->hasMany(FasePedido::class, 'id_pedido', 'id');
    }

    public function situacao(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'idSituacao', 'id');
    }

    public function solicitante_get(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Solicitante', 'id');
    }

    public function responsavel_atendimento(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'ResponsavelAtendimento', 'id');
    }

    public function unidade_judiciaria(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'UnidadeJudiciaria', 'id');
    }

    public function setor_get(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'Setor', 'id');
    }

    public function setorResponsavel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_responsavel', 'id');
    }

    public function complementoSetor(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'ComplementoSetor', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
