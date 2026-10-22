<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Agendamento\Materiais;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Termo extends Model
{
    private const TIPO_TRANSPORTE_CARGA = '2';

    protected $connection = 'emes';

    protected $table = 'egap_mat_termos';

    public $timestamps = false;

    public static $snakeAttributes = false;

    protected $fillable = [
        'date_time',
        'num_termo',
        'ano_termo',
        'atualizado_em',
        'atualizado_por',
        'pedido_no',
        'situacao_entrega',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'atualizado_em' => 'datetime',
        'ano_termo' => 'integer',
        'num_termo' => 'integer',
    ];

    public function pedidoRef(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'pedido_no', 'id');
    }

    public function arquivoDigital(): HasOne
    {
        return $this->hasOne(ArquivoDigital::class, 'termo', 'id')->latestOfMany('id');
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(ArquivoDigital::class, 'termo', 'id');
    }

    public function transferencias(): HasMany
    {
        return $this->hasMany(TransferenciaBemMovel::class, 'Termo', 'id');
    }

    public function ultimaTransferencia(): HasOne
    {
        return $this->hasOne(TransferenciaBemMovel::class, 'Termo', 'id')
            ->latestOfMany('id');
    }

    public function materiais(): HasMany
    {
        return $this->hasMany(Materiais::class, 'id_termo', 'id');
    }

    public function ultimoMaterialTransporte(): HasOne
    {
        return $this->hasOne(Materiais::class, 'id_termo', 'id')
            ->ofMany(['id' => 'max'], function (Builder $query): void {
                $query->whereHas('idSolicitacaoRef', function (Builder $solicitacaoQuery): void {
                    $solicitacaoQuery->where('tipo', self::TIPO_TRANSPORTE_CARGA);
                });
            });
    }

    public function responsavelRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function analisadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'analisado_por', 'id');
    }

    public function getTermoCompletoAttribute(): string
    {
        return "{$this->num_termo} / {$this->ano_termo}";
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_em = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
