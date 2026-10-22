<?php

namespace App\Models\egap\GestaoOrcamentaria;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Projeto extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_orc_projeto';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function acaoRef(): BelongsTo
    {
        return $this->belongsTo(Acao::class, 'acao', 'id');
    }

    public function unidadeJudiciariaRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_judiciaria', 'id');
    }

    public function planoOrcamentarioRef(): BelongsTo
    {
        return $this->belongsTo(PlanoOrcamentario::class, 'plano_orcamentario', 'id');
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
