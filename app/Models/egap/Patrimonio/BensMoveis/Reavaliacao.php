<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reavaliacao extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_reavaliacao'; // ✅ Nome provável baseado no padrão
    public $timestamps = false;
    public static $snakeAttributes = false;

    protected $fillable = [
        'patrimonio',
        'data_reavaliacao',
        'data_disponibilizacao',
        'data_referencia',
        'valor_aquisicao',
        'vida_util_reavaliacao',
        'vida_util_siafi',
        'vida_util',
        'tempo_utilizacao_meses',
        'vida_util_remanescente_meses',
        'valor_mercado',
        'utilizacao_bem_anos',
        'vida_util_estimada_anos',
        'estado_conservacao',
        'pub1',
        'puv',
        'fr',
        'valor_reavaliacao',
        'ajuste_contabil',
        'date_time',
        'atualizado_por',
    ];

    public function bem(): BelongsTo
    {
        return $this->belongsTo(BemMovel::class, 'id_patrimonio', 'id');
    }
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
