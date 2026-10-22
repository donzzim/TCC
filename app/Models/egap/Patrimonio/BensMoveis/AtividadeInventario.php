<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtividadeInventario extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_inv_atividades';
    public $timestamps = false;

    /** ✅ MAPEAMENTO COMPLETO (Baseado no seu print do phpMyAdmin) */
    protected $fillable = [
        'date_time',
        'id_inventario',
        'id_unidade',
        'setor',
        'complemento',
        'inicio',
        'termino',
        'dupla',
        'situacao',
        'qtde_inventariada'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'inicio' => 'datetime',
        'termino' => 'datetime',
        'qtde_inventariada' => 'integer',
    ];

    /** ✅ RELAÇÃO: Qual o inventário "pai"? */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    /** ✅ RELAÇÃO: Unidade Judiciária (mat_setores) */
    public function unidadeRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'id_unidade', 'id');
    }

    /** ✅ RELAÇÃO: Setor específico (mat_setores) */
    public function setorRel(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor', 'id');
    }

    public function complementoRef(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'complemento', 'id');
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(InventarioEquipe::class, 'unidade', 'id');
    }

    public function scopeDoInventario(Builder $query, Inventario|int $inventario): Builder
    {
        $inventarioId = $inventario instanceof Inventario ? $inventario->getKey() : $inventario;

        return $query->where('id_inventario', $inventarioId);
    }

    public function scopeDoSetor(Builder $query, int $unidadeJudiciaria, int $setor): Builder
    {
        return $query
            ->where('id_unidade', $unidadeJudiciaria)
            ->where('setor', $setor);
    }

    public function estaFinalizada(): bool
    {
        return in_array(strtolower(trim((string) $this->situacao)), [
            'finalizado',
            'carga efetuada',
        ], true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
