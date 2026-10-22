<?php

namespace App\Models\egap\PlanoContratacao;

use App\Models\egap\Cadastro\Setores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitePlano extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_limite';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_setor' => 'integer',
        'limite' => 'float',
        'ano_limite' => 'date',
    ];
    public const SETORES_PAI = [1980, 1022, 1990, 1023, 2553, 2554, 1029, 1027, 2307, 2296, 1234, 816, 2289];

    private const ETAPAS_CONSOMEM = [6, 7, 10, 11, 12];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'id_setor', 'id');
    }

    public function scopeDoAno(Builder $query, int|string $ano): Builder
    {
        return $query->whereRaw('YEAR(ano_limite) = ?', [$ano]);
    }

    public static function setorDeReferencia(int $setorGestorId): ?int
    {
        if (in_array($setorGestorId, self::SETORES_PAI, true)) {
            return $setorGestorId;
        }

        return Setores::query()->whereKey($setorGestorId)->value('codigopai') ?: $setorGestorId;
    }

    public static function paraSetorAno(int $setorGestorId, int|string $ano): ?self
    {
        $setorRef = self::setorDeReferencia($setorGestorId);

        if (blank($setorRef)) {
            return null;
        }

        return static::query()
            ->where('id_setor', $setorRef)
            ->doAno($ano)
            ->first();
    }
    public static function consumidoPara(int $setorGestorId, int|string $ano): float
    {
        $setorRef = self::setorDeReferencia($setorGestorId);

        if (blank($setorRef)) {
            return 0.0;
        }

        return (float) PlanoContratacao::query()
            ->where('ano_execucao', $ano)
            ->whereIn('id_etapa', self::ETAPAS_CONSOMEM)
            ->where(function (Builder $query) use ($setorRef): void {
                $query->where('cd_coordenadoria', $setorRef)
                    ->orWhere('secretaria_assessoria', $setorRef);
            })
            ->sum('preco_total');
    }

    public static function disponivelPara(int $setorGestorId, int|string $ano): ?float
    {
        $limite = self::paraSetorAno($setorGestorId, $ano);

        if (! $limite) {
            return null;
        }

        return (float) $limite->limite - self::consumidoPara($setorGestorId, $ano);
    }
}
