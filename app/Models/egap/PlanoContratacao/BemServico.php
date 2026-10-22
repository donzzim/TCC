<?php

namespace App\Models\egap\PlanoContratacao;

use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\GestaoOrcamentaria\PlanoOrcamentario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BemServico extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_pla_bensservicos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_planocontratacao' => 'integer',
        'id_etapa' => 'integer',
        'obj_estrategico' => 'integer',
        'ini_estrategica' => 'integer',
        'quantidade' => 'integer',
        'valor1' => 'float',
        'valor2' => 'float',
        'valor_final' => 'float',
        'valor_global' => 'float',
        'valor_desembolsado_ano_pca' => 'float',
        'quantidade_executada' => 'float',
        'valor1_executado' => 'float',
        'valor2_executado' => 'float',
        'valor_final_executado' => 'float',
        'data_estimada' => 'date',
        'data_inicio' => 'date',
        'data_executada' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->valor_final = (float) $item->valor1 + (float) $item->valor2;

            if (filled($item->valor1_executado) || filled($item->valor2_executado)) {
                $item->valor_final_executado = (float) $item->valor1_executado + (float) $item->valor2_executado;
            }
        });
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoContratacao::class, 'id_planocontratacao', 'id');
    }

    public function situacao(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'id_etapa', 'id');
    }

    public function naturezaDespesa(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'id_naturezadespesa', 'CodigodaClasse');
    }

    public function planoOrcamentario(): BelongsTo
    {
        return $this->belongsTo(PlanoOrcamentario::class, 'id_planoorcamentario', 'codigo');
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoEstrategico::class, 'obj_estrategico', 'id');
    }

    public function iniciativa(): BelongsTo
    {
        return $this->belongsTo(IniciativaEstrategica::class, 'ini_estrategica', 'id');
    }
}
