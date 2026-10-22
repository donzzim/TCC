<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Cadastro\Setores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioUnidade extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_mat_unidadesinventario';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_inventario',
        'unidades',
        'data_inicio',
        'data_termino',
        'situacao',
        'dias',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'data_inicio' => 'datetime',
        'data_termino' => 'datetime',
        'dias' => 'integer',
    ];

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidades', 'id');
    }

    public function setores(): HasMany
    {
        return $this->hasMany(Setores::class, 'CodigoPai', 'unidades')
            ->whereColumn('id', '<>', 'CodigoPai');
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

    public function scopeEmAndamento(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('situacao', '0')
                ->orWhere('situacao', 'Em andamento')
                ->orWhere('situacao', 'A inventariar');
        });
    }

    public function inventariaUnidadeInteira(): bool
    {
        return (bool) $this->unidade?->inventariaUnidadeInteira();
    }

    public function tipoAbrangencia(): string
    {
        return $this->inventariaUnidadeInteira() ? 'unidade' : 'setor';
    }

    public function unidadePaiId(): ?int
    {
        if (! $this->unidade) {
            return null;
        }

        return $this->inventariaUnidadeInteira()
            ? (int) $this->unidade->id
            : (int) $this->unidade->codigopai;
    }

    public function rotuloUnidadeInventariada(): string
    {
        return $this->unidade?->rotuloInventario() ?: (string) $this->unidades;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
