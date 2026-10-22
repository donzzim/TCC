<?php

namespace App\Models\egap\Processo;

use App\Models\egap\Patrimonio\BensImoveis\Processo;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProMaterial extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pro_materiais';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'date_time' => 'datetime',
        'qtde_min' => 'integer',
        'qtde_max' => 'integer',
        'preco' => 'decimal:2',
        'saldo_atual' => 'decimal:2',
    ];

    public function processoRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo', 'id');
    }

    public function materialRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoDetalhada::class, 'material', 'id');
    }

    public function atualizadoPorRelacaoRef(): BelongsTo
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
