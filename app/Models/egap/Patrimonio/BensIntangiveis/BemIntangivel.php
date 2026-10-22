<?php

namespace App\Models\egap\Patrimonio\BensIntangiveis;

use App\Models\egap\Cadastro\ContaContabil;
use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Patrimonio\BensIntangiveis\Fabricante;
use App\Models\egap\Patrimonio\BensIntangiveis\TipoBemIntagivel;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BemIntangivel extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_int_intangivel';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_tipointangivel',
        'id_fabricante',
        'classificacao',
        'nome',
        'versao',
        'quantidade',
        'processo_aquisicao',
        'data_aquisicao',
        'valor_aquisicao',
        'observacao',
        'id_planocontas',
        'id_elementodespesa',
        'atualizado_por',
        'atualizado_em',
        'vida_util_remanescente',
        'inscricao_generica',
        'nota_patrimonial',
        'amortizacao_mensal',
        'amortizacao_acumulada',
        'valor_liquido_contabil',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'data_aquisicao' => 'date',
        'atualizado_em' => 'date',
        'valor_aquisicao' => 'decimal:2',
        'amortizacao_mensal' => 'decimal:2',
        'amortizacao_acumulada' => 'decimal:2',
        'valor_liquido_contabil' => 'decimal:2',
        'quantidade' => 'integer',
    ];

    public function idTipoIntangivelRef(): BelongsTo
    {
        return $this->belongsTo(TipoBemIntagivel::class, 'id_tipointangivel', 'id');
    }

    public function idFabricanteRef(): BelongsTo
    {
        return $this->belongsTo(Fabricante::class, 'id_fabricante', 'id');
    }

    public function idPlanoContasRef(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'id_planocontas', 'id');
    }

    public function idElementoDespesaRef(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'id_elementodespesa', 'id');
    }
    public function atualizadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_em = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
