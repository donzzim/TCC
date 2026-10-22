<?php

namespace App\Models\Contratacao;

use App\Models\Cadastro\NaturezaDespesa;
use App\Models\Planejamento\ObjetivosEstrategicos;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ContratacaoBemServico extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_bemservicos';
    protected $fillable = [
        'setor_id',
        'planocontratacao_id',
        'planoorcamentario_id',
        'natureza_id',
        'etapa_id',
        'objetivo_id',
        'iniciativa_id',
        'atualizado_por',
        'nome',
        'classificacao', //projeto/atividade
        'prioridade', //baixa/media/alta
        'quantidade',
        'valor1',
        'valor2',
        'valor_final',
        'data_estimada',
        'quantidade_executada',
        'valor1_executado',
        'valor2_executado',
        'valorfinal_executado',
        'data_executada',
        'data_inicio',
        'justificativa',
        'renovacao_contrato',
        'criterio', // sustentabilidade/acessibilidade
        'processo_sei',
    ];
    protected function casts(): array
    {
        return [
            'renovacao' => 'boolean',
        ];
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoContratacao::class, 'planocontratacao_id', 'id');
    }
    public function natureza(): BelongsTo
    {
        return $this->belongsTo(NaturezaDespesa::class, 'natureza_id', 'id');
    }
    public function etapa(): BelongsTo
    {
        return $this->belongsTo(EtapaContratacao::class, 'etapa_id', 'id');
    }
    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivosEstrategicos::class, 'objetivo_id', 'id');
    }
    public function iniciativa(): BelongsTo
    {
        return $this->belongsTo(ObjetivosEstrategicos::class, 'iniciativa_id', 'id');
    }
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
