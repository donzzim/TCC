<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Almoxarifado\NotaFiscal;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\ContaContabil;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Cadastro\Fornecedores;
use App\Models\egap\Cadastro\Marcas;
use App\Models\egap\Cadastro\Modelos;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\UnidadesDeMedida;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BemMovel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_patrimonio';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public static $snakeAttributes = false;

    protected $fillable = [
        'TomboSmarapd', 'NumPatrimonio', 'NumerodePatAnterior', 'NumerodeSerie',
        'Produto', 'ContaContabil', 'DescricaoResumidadoBem', 'Descricao',
        'Marca', 'Modelo', 'TipodoBem', 'EstadodeConservacao', 'Voltagem',
        'SituacaoBem', 'ClassificacaoInservivel', 'UnidadeJudiciaria', 'Setor',
        'ComplementoSetor', 'AndarSetor', 'Unidade', 'DataCadastro', 'Valor',
        'DatadeIncorporacao', 'Lote', 'DatadeVencimento', 'NumeracaoInicial',
        'NumeracaoFinal', 'MesdeReferencia', 'Fornecedor', 'NotaFiscal',
        'FormaAquisicao', 'SiglaUnidadeGestora', 'Categoria', 'Combustivel',
        'Placa', 'Chassi', 'Renavam', 'VidaUtil', 'DatadaReavaliacao',
        'ValordaReavaliacao', 'ValordeMercado', 'AcertoContabil', 'UtilizacaodoBem',
        'VidaUtilEstimada', 'EC', 'PUB1', 'PUB2', 'PUV', 'FR', 'ValorReavaliado',
        'VidaUtilSIAFi', 'UtilizacaodoBemMeses', 'DepreciacaoMensal',
        'DepreciacaoAcumulada', 'TestedeImpairment', 'DataDisponibilizacao',
        'Observacao', 'date_time', 'Usuario', 'NumTomboSmarapd', 'ProcessoBaixa',
        'DataBaixa', 'ValorAquisicao', 'AnoFabricacao', 'AnoModelo', 'ValorResidual',
        'Garantia', 'numero_processo', 'nota_empenho', 'nota_liquidacao',
        'data_liquidacao', 'VidaUtilReavaliacao', 'id_descricaodetalhada',
        'grupo', 'acuracia', 'unidade_gestora', 'sit_inventario', 'id_inventario',
        'situacao_contabil', 'data_situacao_contabil',
    ];

    protected $casts = [
        'DatadeIncorporacao' => 'datetime',
        'ValorAquisicao' => 'decimal:2',
    ];

    public function descricaoResumidaBemRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoResumida::class, 'DescricaoResumidadoBem', 'id');
    }

    public function unidadeJudiciariaRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'UnidadeJudiciaria', 'id');
    }

    public function descricaoDetalhadaRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoDetalhada::class, 'id_descricaodetalhada', 'id');
    }

    public function setorRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'Setor', 'id');
    }

    public function produtoRef(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'Produto', 'id');
    }

    public function fornecedorRef(): BelongsTo
    {
        return $this->belongsTo(Fornecedores::class, 'Fornecedor', 'id');
    }

    public function complementoSetorRef(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'ComplementoSetor', 'id');
    }

    public function situacaoBemRef(): BelongsTo
    {
        return $this->belongsTo(SituacaoBemMovel::class, 'SituacaoBem', 'id');
    }

    public function marcaRef(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'Marca', 'id');
    }

    public function contaContabilRef(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'ContaContabil', 'id');
    }
    public function unidadeMedidaRef(): BelongsTo
    {
        return $this->belongsTo(UnidadesDeMedida::class, 'Unidade', 'id');
    }

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    public function modeloRef(): BelongsTo
    {
        return $this->belongsTo(Modelos::class, 'Modelo', 'id');
    }

    public function notaFiscalRef(): BelongsTo
    {
        return $this->belongsTo(NotaFiscal::class, 'NotaFiscal', 'id');
    }

    public function elementoDespesaRef(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'Produto', 'id');
    }

    public function inventarioRef(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function ultimaTransferencia(): HasOne
    {
        return $this->hasOne(TransferenciaBemMovel::class, 'NumPatrimonio', 'NumPatrimonio')
            ->latestOfMany('id');
    }

    public function ultimaTransferenciaValidada(): HasOne
    {
        return $this->hasOne(TransferenciaBemMovel::class, 'NumPatrimonio', 'NumPatrimonio')
            ->ofMany(['id' => 'max'], function (Builder $query): void {
                $query->whereHas(
                    'termoRel.arquivoDigital',
                    fn (Builder $query) => $query->where('situacao', ArquivoDigital::SITUACAO_VALIDADO)
                );
            });
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
