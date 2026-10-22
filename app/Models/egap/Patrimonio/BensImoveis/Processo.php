<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\GestaoOrcamentaria\Projeto;
use App\Models\egap\Processo\MatTipoProcesso;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\Fornecedores;
use App\Models\egap\UserEgap;
use App\Models\egap\Processo\MatAnexoProcesso;
use App\Models\egap\Processo\ProMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_mat_processos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function tipoProcessoRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(MatTipoProcesso::class, 'id_tipo_processo', 'id');
    }

    public function unidadeRequisitanteRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidade_demandante', 'id');
    }

    public function processoPaiRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_processo_pai', 'id');
    }

    public function fornecedorRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(Fornecedores::class, 'id_fornecedor', 'id');
    }

    public function gestorTitularRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'gestor_titular', 'id');
    }

    public function gestorSubstitutoRelacaoRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'gestor_substituto', 'id');
    }

    public function documentacoesRelacaoRef(): HasMany
    {
        return $this->hasMany(MatAnexoProcesso::class, 'num_processo', 'id');
    }

    public function materiaisRelacaoRef(): HasMany
    {
        return $this->hasMany(ProMaterial::class, 'processo', 'id');
    }

    public function projetoAtividadeRef(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'projeto_atividade', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
