<?php

namespace App\Models\egap\GestaoOrcamentaria;

use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Planejamento extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_orc_orcamento';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function atualizadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function projetoRef(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'projeto', 'id');
    }

    public function elementoDespesaRef(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'elemento_despesa', 'id');
    }

    public function unidadeRequisitanteRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_requisitante', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
