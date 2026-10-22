<?php

namespace App\Models\Contratacao;

use App\Models\Cadastro\Fornecedores;
use App\Models\Escola\Cursos;
use App\Models\Perfil;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Processo extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_processos';
    protected $fillable = [
        'setor_id',
        'tipoprocesso_id',
        'fornecedor_id',
        // 'curso_id',
        // 'instrutor_id',
        'numero',
        'descricao',
        'fisica_juridica',
        'ativo',
        'atualizado_por',
    ];
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function gestores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cnt_gestores', 'processo_id', 'gestor_id');
    }
    public function processo_eventos()
    {
        return $this->hasMany(ProcessoEvento::class, 'processo_id', 'id');
    }
    public function reservas()
    {
        return $this->hasMany(ProcessoEvento::class, 'processo_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.reserva'));
    }
    public function empenhos()
    {
        return $this->hasMany(ProcessoEvento::class, 'processo_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.empenho'));
    }
    public function liquidacoes()
    {
        return $this->hasMany(ProcessoEvento::class, 'processo_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.liquidacao'));
    }
    public function ordemservicos()
    {
        return $this->hasMany(ProcessoEvento::class, 'processo_id', 'id')
            ->where('tipoevento_id', config('settings.processoevento.ordemservico'));
    }
    // public function reservas()
    // {
    //     return $this->belongsToMany(TipoProcessoEvento::class, 'cnt_processoeventos', 'processo_id', 'tipoevento_id')
    //         // ->using(ProcessoEvento::class)
    //         ->withPivot('numero', 'numero_documento')
    //         ->wherePivot('tipoevento_id', config('settings.processoevento.reserva'));
    // }
    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedores::class, 'fornecedor_id', 'id');
    }
    // public function cursos(): BelongsToMany
    // {
    //     return $this->belongsToMany(ProcessoCursos::class,'cnt_processocursos', 'curso_id', 'id');
    // }
    // public function instrutor(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'instrutor_id', 'id');
    // }
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoProcesso::class, 'tipoprocesso_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        // static::saving(function (ProcessoEvento $row) {
        //     if ($row->numero_documento && empty($row->data_nota)) {
        //         $row->data_nota = now();
        //     }
        // });
    }
}
