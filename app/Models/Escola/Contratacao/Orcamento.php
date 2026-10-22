<?php

namespace App\Models\Contratacao;

use App\Models\Cadastro\NaturezaDespesa;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Orcamento extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_orcamento';
    protected $fillable = [
        'setor_id',
        'natureza_id',
        'planoorcamentario_id',
        'atualizado_por',
        'descricao',
        'ano',
        'previsao_1instancia',
        'previsao_2instancia',
        'previsao_total',
        'valor_1instancia',
        'valor_2instancia',
        'valor_total',
        'saldo_1instancia',
        'saldo_2instancia',
        'saldo_total',
        'observacao',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function natureza(): BelongsTo
    {
        return $this->belongsTo(NaturezaDespesa::class, 'natureza_id', 'id');
    }
    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoOrcamentario::class, 'planoorcamentario_id', 'id');
    }
    public function eventos()
    {
        return $this->hasMany(OrcamentoEvento::class, 'orcamento_id', 'id');
    }
    public function reservas()
    {
        return $this->hasMany(ProcessoEvento::class, 'orcamento_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.reserva'));
    }
    public function empenhos()
    {
        return $this->hasMany(ProcessoEvento::class, 'orcamento_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.empenho'));
    }
    public function liquidacoes()
    {
        return $this->hasMany(ProcessoEvento::class, 'orcamento_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.liquidacao'));
    }
    public function ordemservicos()
    {
        return $this->hasMany(ProcessoEvento::class, 'orcamento_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.ordemservico'));
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
