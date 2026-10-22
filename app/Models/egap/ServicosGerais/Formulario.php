<?php

namespace App\Models\egap\ServicosGerais;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Formulário de avaliação de uma localidade num período (legado:
 * ava_formulario) — um por (período, setor).
 *
 * O `contrato` do legado (coluna denormalizada em ava_formulario) some
 * daqui: dá pra chegar no contrato através de {@see periodo()}, então
 * {@see contrato()} é apenas uma leitura conveniente, não uma coluna própria.
 *
 * `nota_gestor` substitui `ng` (nota manual do gestor, 0-10, default 10 no
 * legado). `tem_falta_sem_substituicao`/`observacao_falta` substituem
 * `resposta_falta` ('S'/'N') e `observacao`.
 */
class Formulario extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_formularios';

    public $timestamps = false;

    protected $fillable = [
        'periodo_id',
        'setor_id',
        'fiscal_id',
        'gestor_id',
        'status',
        'tem_falta_sem_substituicao',
        'observacao_falta',
        'nota_gestor',
        'date_time',
    ];

    protected $casts = [
        'status' => StatusFormulario::class,
        'tem_falta_sem_substituicao' => 'boolean',
        'nota_gestor' => 'integer',
        'date_time' => 'datetime',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function contrato(): HasOneThrough
    {
        return $this->hasOneThrough(
            Contrato::class,
            Periodo::class,
            'id',
            'id',
            'periodo_id',
            'contrato_id',
        );
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id');
    }

    public function fiscal(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'fiscal_id');
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'gestor_id');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(FormularioResposta::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
