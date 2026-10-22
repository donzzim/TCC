<?php

namespace App\Models\egap\ServicosGerais;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\TipoContrato;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contrato terceirizado avaliado periodicamente (legado: ava_contrato).
 *
 * O `id_tipo` string livre do legado ('li'/'co'/'te'/'tr'/'ag') virou
 * {@see TipoContrato}; o `situacao` inteiro (1/0) virou o boolean `vigente`.
 */
class Contrato extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_contratos';

    public $timestamps = false;

    protected $fillable = [
        'nome',
        'tipo',
        'vigente',
        'date_time',
        'usuario_id',
    ];

    protected $casts = [
        'tipo' => TipoContrato::class,
        'vigente' => 'boolean',
        'date_time' => 'datetime',
    ];

    public function periodos(): HasMany
    {
        return $this->hasMany(Periodo::class);
    }

    public function localidades(): HasMany
    {
        return $this->hasMany(ContratoLocalidade::class);
    }

    public function localidadesAtivas(): HasMany
    {
        return $this->localidades()->where('ativo', true);
    }

    public function gestores(): HasMany
    {
        return $this->hasMany(ContratoGestor::class);
    }

    public function fiscais(): HasMany
    {
        return $this->hasMany(ContratoFiscal::class);
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario_id');
    }

    public function usaQuestionario(): bool
    {
        return $this->tipo?->usaQuestionario() ?? false;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
