<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaBemMovel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_transferencia';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'NumPatrimonio',
        'SetorAnterior',
        'SetorAtual',
        'Usuario',
        'UnidadeAnterior',
        'UnidadeAtual',
        'ComplementoAnterior',
        'ComplementoAtual',
        'AndarAnterior',
        'AndarAtual',
        'Termo',
        'pedido_no',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function bem(): BelongsTo
    {
        return $this->belongsTo(BemMovel::class, 'NumPatrimonio', 'NumPatrimonio');
    }

    public function unidadeAnteriorRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'UnidadeAnterior', 'id');
    }

    public function unidadeAtualRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'UnidadeAtual', 'id');
    }

    public function setorAnteriorRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'SetorAnterior', 'id');
    }

    public function setorAtualRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'SetorAtual', 'id');
    }

    public function complementoAnteriorRel(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'ComplementoAnterior', 'id');
    }

    public function complementoAtualRel(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'ComplementoAtual', 'id');
    }

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    public function termoRel(): BelongsTo
    {
        return $this->belongsTo(Termo::class, 'Termo', 'id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'pedido_no', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }

    public function getSetorTransferenciaAttribute(): string
    {
        return "{$this->SetorAnterior} -> {$this->SetorAtual}";
    }

    public function getUnidadeTransferenciaAttribute(): string
    {
        return "{$this->UnidadeAnterior} -> {$this->UnidadeAtual}";
    }
}
