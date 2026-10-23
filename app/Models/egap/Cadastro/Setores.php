<?php

namespace App\Models\egap\Cadastro;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Setores extends Model
{
    protected $connection = 'emes';

    protected $table = 'setores';

    protected $fillable = [
        'codigopai',
        'unidadeorganizacional',
        'setor',
        'codigodauo',
        'cd_orgao',
        'centrocusto',
        'comarca',
        'vara',
        'ordem',
        'email',
        'cns',
        'presidencia',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::created(function (self $setor): void {
            $setor->forceFill([
                'cd_orgao' => $setor->getKey(),
            ])->saveQuietly();
        });
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'codigopai', 'id');
    }

    public function unidadeOrganizacionalRaiz(): BelongsTo
    {
        return $this->belongsTo(self::class, 'codigodauo', 'id');
    }

    /** Unidades organizacionais (a linha raiz aponta para si mesma em codigopai). */
    public function scopeUnidadesInventariaveis(Builder $query): Builder
    {
        return $query->whereColumn('id', 'codigopai');
    }

    public function scopeSetoresInventariaveis(Builder $query): Builder
    {
        return $query
            ->whereNotNull('codigopai')
            ->whereColumn('id', '<>', 'codigopai');
    }

    public function scopeFilhosDe(Builder $query, int $unidadeId): Builder
    {
        return $query
            ->where('codigopai', $unidadeId)
            ->whereColumn('id', '<>', 'codigopai');
    }

    public function inventariaUnidadeInteira(): bool
    {
        return $this->id !== null
            && $this->codigopai !== null
            && (int) $this->id === (int) $this->codigopai;
    }

    public function rotuloInventario(): string
    {
        if ($this->inventariaUnidadeInteira()) {
            return $this->unidadeorganizacional ?: $this->setor ?: "Unidade {$this->id}";
        }

        return collect([
            $this->pai?->unidadeorganizacional ?: $this->pai?->setor,
            $this->setor,
        ])
            ->filter()
            ->join(' - ') ?: "Setor {$this->id}";
    }

    public static function descendentesIds(int $setorId): array
    {
        $sql = <<<'SQL'
        WITH RECURSIVE descendentes AS (
            SELECT id FROM setores
            WHERE codigopai = ? AND id <> codigopai
            UNION ALL
            SELECT s.id FROM setores s
            JOIN descendentes d ON s.codigopai = d.id
            WHERE s.id <> s.codigopai
        )
        SELECT id FROM descendentes
        SQL;

        return array_column(
            DB::connection((new static)->getConnectionName())->select($sql, [$setorId]),
            'id'
        );
    }
}
