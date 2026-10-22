<?php

namespace App\Models\Escola;

use App\Models\Cadastro\Tabelas;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Contratacao\TipoProcesso;
use App\Models\Perfil;
use App\Models\User;
use App\Services\MoodleService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;

/**
 * @property int|null $curso_id
 * @property int|null $instrutor_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contratacao\ProcessoCursos> $processosCursos
 */
class Instrutores extends Pivot
{
    protected $table = 'esc_instrutores';

    protected $fillable = [
        'curso_id',
        'instrutor_id',
        'titulacao_id',
        'tipoprocesso_id',
        'tipoatividade_id',
        'carga_horaria',
        'valor_hora',
        'valor_total',
        'status_documentacao',
        'dados_bancarios',
        'cnpj',
        'razao_social',
        'atualizado_por',
    ];

    public $incrementing = true;

    public $primarykey = 'id';

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'dados_bancarios' => 'array',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }

    public function instrutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrutor_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function titulacao(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'titulacao_id', 'id');
    }

    public function tipoAtividade(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'tipoatividade_id', 'id');
    }

    public function processos(): HasMany
    {
        return $this->hasMany(ProcessoCursos::class, 'instrutor_id', 'id');
    }

    public function tipoProcesso(): BelongsTo
    {
        return $this->belongsTo(TipoProcesso::class, 'tipoprocesso_id', 'id');
    }

    /**
     * Traz todos os processos desse instrutor para podermos carregar tudo em 1 query (Eager Load)
     */
    /** @return HasMany<ProcessoCursos, $this> */
    public function processosCursos(): HasMany
    {
        return $this->hasMany(ProcessoCursos::class, 'instrutor_id', 'instrutor_id');
    }

    /**
     * Acessor Inteligente: Filtra em memória a collection já carregada, evitando N+1
     * Pode ser chamado no código como $record->processoCursoCapa
     */
    public function getProcessoCursoCapaAttribute()
    {
        return $this->processosCursos->where('curso_id', $this->curso_id)->first();
    }

    /**
     * Atalho direto para pegar os dados do Usuário/Perfil do instrutor a partir da contratação.
     */
    public function perfil(): HasOneThrough
    {
        return $this->hasOneThrough(
            Perfil::class,
            User::class,
            'id', // Local key on users table
            'user_id', // Foreign key on perfils table
            'instrutor_id', // Local key on esc_instrutores table
            'id' // Local key on users table
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Instrutores $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (Instrutores $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::created(function (Instrutores $row) {

            if ($row->curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                app(MoodleService::class)->matricularInstrutorCurso($row->curso); // insctrutor
            }
        });

        static::updated(function (Instrutores $row) {
            if ($row->curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                app(MoodleService::class)->matricularInstrutorCurso($row->curso); // alunos
            }
        });

        static::deleted(function (Instrutores $row) {
            if ($row->curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                app(MoodleService::class)->removerInstrutorCurso($row);
            }
        });
    }
}
