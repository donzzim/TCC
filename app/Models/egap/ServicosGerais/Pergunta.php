<?php

namespace App\Models\egap\ServicosGerais;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\TipoContrato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de perguntas do questionário de avaliação, por tipo de contrato
 * (legado: ava_perguntas, referenciada em avarelatorio.php — só o texto era
 * consultado ali, os pesos ficavam hardcoded em loops PHP dentro de
 * avaforms.php e variavam por tipo: 'li' usava pesos 50/150/20 conforme a
 * pergunta, 'co'/'te' usavam 10 fixo para todas).
 *
 * `peso_maximo` substitui esses valores hardcoded — cadastrando aqui, a
 * geração de formulários ({@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioGeradorService})
 * não precisa mais de um `if/elseif` por tipo de contrato.
 */
class Pergunta extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_perguntas';

    public $timestamps = false;

    protected $fillable = [
        'tipo_contrato',
        'ordem',
        'texto',
        'peso_maximo',
    ];

    protected $casts = [
        'tipo_contrato' => TipoContrato::class,
        'ordem' => 'integer',
        'peso_maximo' => 'integer',
    ];

    public function respostas(): HasMany
    {
        return $this->hasMany(FormularioResposta::class);
    }

    public function scopeParaTipo(Builder $query, TipoContrato $tipo): Builder
    {
        return $query->where('tipo_contrato', $tipo)->orderBy('ordem');
    }
}
