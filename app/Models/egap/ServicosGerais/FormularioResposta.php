<?php

namespace App\Models\egap\ServicosGerais;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusResposta;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resposta do fiscal a uma {@see Pergunta} dentro de um {@see Formulario}
 * (legado: ava_respostas).
 *
 * `estrelas` substitui `nfc` (0-5, o antigo ícone de estrela); `pontos`
 * substitui `nans` (pontuação efetivamente somada na nota do período,
 * calculada a partir de `estrelas` e do `Pergunta::peso_maximo`).
 */
class FormularioResposta extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_formulario_respostas';

    public $timestamps = false;

    protected $fillable = [
        'formulario_id',
        'pergunta_id',
        'estrelas',
        'pontos',
        'observacao',
        'status',
        'date_time',
    ];

    protected $casts = [
        'estrelas' => 'integer',
        'pontos' => 'integer',
        'status' => StatusResposta::class,
        'date_time' => 'datetime',
    ];

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
