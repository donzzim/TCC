<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\Fornecedores;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaFiscal extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_alm_notafiscal';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'num_documento',
        'situacao',
        'data_situacao',
        'fornecedor',
        'data_documento',
        'tipo_documento',
        'observacao',
        'atualizado_por',
        'valor_total',
        'estoque',
        'unidade_judiciaria',
        'setor',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'data_situacao' => 'datetime',
        'data_documento' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(ItemNotaFiscal::class, 'id_notafiscal', 'id');
    }

    public function itensNotaFiscal(): HasMany
    {
        return $this->itens();
    }

    public function situacaoRef(): BelongsTo
    {
        return $this->belongsTo(SituacaoNotaFiscal::class, 'situacao', 'id');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function fornecedorRef(): BelongsTo
    {
        return $this->belongsTo(Fornecedores::class, 'fornecedor', 'id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento', 'id');
    }

    public function unidadeJudiciaria(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_judiciaria');
    }

    public function setorRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }

    public function calcularValorTotal(): void
    {
        $this->valor_total = $this->itens()
            ->sum('total_item');
    }
}
