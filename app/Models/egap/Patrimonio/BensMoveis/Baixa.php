<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\egap\UserEgap;
use Illuminate\Support\Facades\Auth;

class Baixa extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_baixa';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'Usuario',
        'NumeroProcesso',
        'DataBaixa',
        'Requisitante',
        'RequisitanteCnpj',
        'Observacao',
        'Endereco',
    ];

    /**
     * ✅ GATILHO DE CRIAÇÃO:
     * Grava automaticamente o usuário e a data/hora.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemBaixa::class, 'id_baixa', 'id');
    }

    /**
     * ✅ RELAÇÃO PADRONIZADA: responsavelRef
     */
    public function responsavelRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }
}
