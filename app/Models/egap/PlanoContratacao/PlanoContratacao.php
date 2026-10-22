<?php

namespace App\Models\egap\PlanoContratacao;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\GestaoOrcamentaria\PlanoOrcamentario;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoContratacao extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_planocontratacao';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_solicitante' => 'integer',
        'id_etapa' => 'integer',
        'cd_coordenadoria' => 'integer',
        'secretaria_assessoria' => 'integer',
        'preco_total' => 'float',
        'ano_execucao' => 'integer',
    ];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_solicitante', 'id');
    }

    public function situacao(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'id_etapa', 'id');
    }

    public function coordenadoriaRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'cd_coordenadoria', 'id');
    }

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'secretaria_assessoria', 'id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(BemServico::class, 'id_planocontratacao', 'id');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoPlano::class, 'id_planocontratacao', 'id');
    }

    public function equipe(): HasMany
    {
        return $this->hasMany(EquipePlanejamento::class, 'id_planocontratacao', 'id');
    }

    public function scopeDoSetor(Builder $query, array $setorIds): Builder
    {
        return $query->where(function (Builder $query) use ($setorIds): void {
            $query->whereIn('cd_coordenadoria', $setorIds)
                ->orWhereIn('secretaria_assessoria', $setorIds);
        });
    }

    public function scopeDoAno(Builder $query, int|string $ano): Builder
    {
        return $query->where('ano_execucao', $ano);
    }
    public static function anosDisponiveis(): array
    {
        $anos = static::query()
            ->whereNotNull('ano_execucao')
            ->distinct()
            ->orderByDesc('ano_execucao')
            ->pluck('ano_execucao')
            ->map(fn ($ano): int => (int) $ano)
            ->all();

        $padrao = (int) date('Y') + 1;

        return array_values(array_unique([...$anos, $padrao, (int) date('Y')]));
    }
    public function recalcularPrecoTotal(): void
    {
        $this->preco_total = (float) $this->itens()->sum('valor_final');
        $this->save();
    }
}
