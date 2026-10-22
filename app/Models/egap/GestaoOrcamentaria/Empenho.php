<?php

namespace App\Models\egap\GestaoOrcamentaria;

use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensImoveis\Processo;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empenho extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_orc_empenho';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function processoRef(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_no', 'id');
    }

    public function elementoDespesaRef(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'elemento_despesa', 'id');
    }

    public function reservaRef(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'no_reserva', 'id');
    }

    public function gestorTitularRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'gestor_titular', 'id');
    }

    public function gestorSubstitutoRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'gestor_substituto', 'id');
    }

    public function unidadeRequisitanteRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_requisitante', 'id');
    }

    public function atualizadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
