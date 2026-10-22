<?php

namespace App\Models\Contratacao;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ProcessoEvento extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_processoeventos';
    protected $fillable = [
        'setor_id',
        'processo_id',
        'tipoevento_id',
        'orcamento_id',
        'processoevento_id',
        'unidadegestora_id',
        'programatrabalho_id',
        'natureza_id',
        'atualizado_por',
        'numero',
        'numero_documento',
        'data_nota',
        'instancia',
        'quantidade',
        'descricao',
        'valor',
        'data',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id', 'id');
    }
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(ProcessoEvento::class, 'processoevento_id', 'id');
    }
    public function empenho(): BelongsTo
    {
        return $this->belongsTo(ProcessoEvento::class, 'processoevento_id', 'id');
    }
    public function tipoevento(): BelongsTo
    {
        return $this->belongsTo(TipoProcessoEvento::class, 'tipoevento_id', 'id');
    }
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {

        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::saving(function (ProcessoEvento $row) {
            if ($row->numero_documento && empty($row->data_nota)) {
                $row->data_nota = now();
            }
        });
    }
}
