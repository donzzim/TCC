<?php

namespace App\Models\egap\GestaoOrcamentaria;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoOrcamentario extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_orc_planoorcamentario';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function atualizadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function setorResponsavelRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_responsavel', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
