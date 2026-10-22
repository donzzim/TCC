<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Almoxarifado\NotaFiscal;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DescricaoDetalhada extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_mat_descricaodetalhada';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'descricao_resumida',
        'descricao_detalhada',
        'marca',
        'modelo',
        'valor_mercado',
        'atualizado_por',
        'imagem',
        'visibilidade',
        'pdf',
        'unidade_medida',
        'item_estoque',
    ];

    protected $casts = [
        'valor_mercado' => 'decimal:2',
        'date_time' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MOvimentacaoEstoque::class, 'material', 'id');
    }

    public function marca_text(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'marca', 'id');
    }

    public function modelo_text(): BelongsTo
    {
        return $this->belongsTo(Modelos::class, 'modelo', 'id');
    }

    public function descricao_resumida_text(): BelongsTo
    {
        return $this->belongsTo(DescricaoResumida::class, 'descricao_resumida', 'id');
    }

    public function unidadeMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadesDeMedida::class, 'unidade_medida', 'id');
    }

    public function atualizado_por_usuario(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function notaFiscal(): HasMany
    {
        return $this->hasMany(NotaFiscal::class, 'id_notafiscal', 'id');
    }

    public function bensMoveis(): HasMany
    {
        return $this->hasMany(BemMovel::class, 'id_descricaodetalhada', 'id');
    }
}
