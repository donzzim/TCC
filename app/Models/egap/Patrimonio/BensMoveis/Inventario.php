<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Inventario extends Model
{
    public const SITUACAO_A_INVENTARIAR = '0';
    public const SITUACAO_EM_EXECUCAO = '1';
    public const SITUACAO_CONCLUIDO = '2';

    protected $connection = 'emes';

    protected $table = 'egap_mat_inventario';

    public $timestamps = false;
    public static $snakeAttributes = false;

    protected $fillable = [
        'date_time',
        'num_inventario',
        'ano_inventario',
        'inicio_inventario',
        'termino_inventario',
        'atualizado_em',
        'atualizado_por',
        'situacao',
        'dias'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'inicio_inventario' => 'datetime',
        'termino_inventario' => 'datetime',
        'atualizado_em' => 'datetime',
        'dias' => 'integer',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(ItemInventario::class, 'id_inventario', 'id');
    }

    public function unidadesInventariadas(): HasMany
    {
        return $this->hasMany(InventarioUnidade::class, 'id_inventario', 'id');
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(AtividadeInventario::class, 'id_inventario', 'id');
    }

    public function comissoes(): HasMany
    {
        return $this->hasMany(InventarioComissao::class, 'id_inventario', 'id');
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(InventarioEquipe::class, 'id_inventario', 'id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('situacao', self::SITUACAO_A_INVENTARIAR)
                ->orWhere('situacao', 'A inventariar')
                ->orWhere('situacao', self::SITUACAO_EM_EXECUCAO)
                ->orWhere('situacao', 'Em execução')
                ->orWhere('situacao', 'Em andamento')
                ->orWhere('situacao', 'Em execucao');
        });
    }

    public function scopeFinalizados(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('situacao', self::SITUACAO_CONCLUIDO)
                ->orWhere('situacao', 'Concluído')
                ->orWhere('situacao', 'Concluido')
                ->orWhere('situacao', 'Finalizado');
        });
    }

    public static function situacoes(): array
    {
        return [
            self::SITUACAO_A_INVENTARIAR => 'A inventariar',
            self::SITUACAO_EM_EXECUCAO => 'Em execução',
            self::SITUACAO_CONCLUIDO => 'Concluído',
        ];
    }

    public static function rotuloSituacao(?string $situacao): string
    {
        return match ($situacao) {
            self::SITUACAO_A_INVENTARIAR, 'A inventariar' => 'A inventariar',
            self::SITUACAO_EM_EXECUCAO, 'Em execução', 'Em execucao', 'Em andamento' => 'Em execução',
            self::SITUACAO_CONCLUIDO, 'Concluído', 'Concluido', 'Finalizado' => 'Concluído',
            default => (string) $situacao,
        };
    }

    public static function corSituacao(?string $situacao): string
    {
        return match ($situacao) {
            self::SITUACAO_A_INVENTARIAR, 'A inventariar' => 'warning',
            self::SITUACAO_EM_EXECUCAO, 'Em execução', 'Em execucao', 'Em andamento' => 'info',
            self::SITUACAO_CONCLUIDO, 'Concluído', 'Concluido', 'Finalizado' => 'success',
            default => 'gray',
        };
    }

    public function estaAberto(): bool
    {
        return in_array($this->situacaoNormalizada(), [
            self::SITUACAO_EM_EXECUCAO,
            'em execução',
            'em execucao',
            'em andamento',
        ], true);
    }

    public function estaAInventariar(): bool
    {
        return in_array($this->situacaoNormalizada(), [
            self::SITUACAO_A_INVENTARIAR,
            'a inventariar',
            '',
        ], true);
    }

    public function estaAtivo(): bool
    {
        return $this->estaAberto() || $this->estaAInventariar();
    }

    public function estaFinalizado(): bool
    {
        return in_array($this->situacaoNormalizada(), [
            self::SITUACAO_CONCLUIDO,
            'concluído',
            'concluido',
            'finalizado',
        ], true);
    }

    public function podeAbrir(): bool
    {
        return $this->estaAInventariar();
    }

    public function podeFechar(): bool
    {
        return $this->estaAtivo();
    }

    private function situacaoNormalizada(): string
    {
        return strtolower(trim((string) $this->situacao));
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_em = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });

        // apagar um inventário com conferência deixaria itens e atividades órfãos
        static::deleting(function (self $model): void {
            if ($model->temConferencia()) {
                throw new \RuntimeException('Este inventário já tem conferência registrada e não pode ser excluído.');
            }
        });
    }

    public function temConferencia(): bool
    {
        return $this->itens()->exists() || $this->atividades()->exists();
    }

    public function scopeEmExecucao(Builder $query): Builder
    {
        return $query->whereIn('situacao', [self::SITUACAO_EM_EXECUCAO, 'Em execução', 'Em execucao', 'Em andamento']);
    }
}
