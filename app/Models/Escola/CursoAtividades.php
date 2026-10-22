<?php

namespace App\Models\Escola;

use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Tags;
use App\Models\User;
use App\Services\AgendaService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * @property \Carbon\Carbon|null $inicio
 * @property \Carbon\Carbon|null $termino
 */
class CursoAtividades extends Model
{
    protected $table = 'esc_cursos';

    protected $fillable = [
        'curso_id',
        'legado_id',
        'uuid',
        'curso_id',
        'moodle_course_id',
        'demanda_id',
        'acao_id',
        'setor_id',
        'areatematica_id',
        'modalidade_id',
        'realizacao_id',
        'tipoaformacao_id',
        'tipoatividade_id',
        'tipoevento_id',
        'tipolocal_id',
        'instituicao_id',
        'atualizado_por',
        'ano',
        'titulo',
        'certificavel',
        'descricao',
        'justificativa',
        'objetivo_geral',
        'conteudo_programatico',
        'abordagem_pedagogica',
        'avaliacao_cursistas',
        'referencias_bibliograficas',
        'carga_horaria',
        'numero_vagas',
        'criterio_selecao',
        'aprovacao',
        'externo',
        'inicio',
        'termino',
        'inicio_inscricao',
        'termino_inscricao',
        'turno',
        'tolerancia_inicio',
        'tolerancia_termino',
        'liberar_divulgacao',
        'liberar_inscricao',
        'liberar_frequencia',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'liberar_divulgacao' => 'boolean',
            'liberar_inscricao' => 'boolean',
            'liberar_frequencia' => 'boolean',
            'certificavel' => 'boolean',
            'inicio' => 'datetime',
            'termino' => 'datetime',
            'externo' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function agendas(): MorphMany
    {
        return $this->morphMany(Agenda::class, 'agendaable');
    }

    public function alunos(): HasMany
    {
        return $this->hasMany(CursoAlunos::class, 'curso_id', 'id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }

    public function vagas(): int
    {
        $inscritosCount = $this->alunos()
            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
            ->count();

        if ($this->numero_vagas) {
            $capacidade = $this->numero_vagas;
        } else {
            $tipoLocal = TipoLocal::find($this->tipolocal_id);

            if ($tipoLocal) {
                $capacidade = $tipoLocal->capacidade() ?? 5000;
            } else {
                $capacidade = 5000;
            }
        }

        return $capacidade - $inscritosCount;
    }

    public function certMorph(): MorphMany
    {
        return $this->morphMany(CursoCertificados::class, 'certificable');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function avaliacao(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'curso_id', 'curso_id');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(CursoFrequencias::class, 'curso_id', 'id');
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(CursoCertificados::class, 'curso_id', 'id');
    }

    public function temAtividades(): bool
    {
        return false;
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tags::class, 'taggable', 'taggables', 'tag_id', 'taggable_id', 'id', 'id', 'tags');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFuncional::class, 'atividade_id', 'id');
    }

    public function tipoLocal(): BelongsTo
    {
        return $this->belongsTo(TipoLocal::class, 'tipolocal_id', 'id');
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(CursoArquivos::class, 'curso_id', 'id');
    }

    public function instrutores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'esc_instrutores', 'curso_id', 'instrutor_id')
            ->using(Instrutores::class)
            ->withPivot('atualizado_por', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    protected static function atualizarCurso($curso)
    {
        $atv = CursoAtividades::where('curso_id', '=', $curso->id)->where('certificavel', true);
        $carga_horaria = $atv->sum('carga_horaria');

        $agendas = AgendaService::buscarAgendas($curso);
        $inicio = $agendas->min('inicio');
        $termino = $agendas->max('termino');

        return Cursos::withoutEvents(function () use ($curso, $inicio, $termino, $carga_horaria) {
            Cursos::withoutGlobalScopes()
                ->whereKey($curso->id)
                ->update([
                    'inicio' => $inicio,
                    'termino' => $termino,
                    'carga_horaria' => $carga_horaria,
                ]);
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope('atividades', function (Builder $builder) {
            $builder->where('esc_cursos.id', '>', 0)->whereNotNull('esc_cursos.curso_id');
        });

        static::creating(function (CursoAtividades $row) {
            $row->uuid = Uuid::uuid4()->toString();

            $curso = Cursos::find($row->curso_id);
            if (! $row->tipolocal_id) {
                $row->tipolocal_id = $curso->tipolocal_id;
            }
            if (AgendaService::verificaConflito($row->tipolocal_id, $row->termino, $row->inicio, [], [$row->curso_id])) {
                Log::info("Conflito de agenda ao criar atividade para o curso {$curso->id}");

                return false;
            }

            $row->uuid = Uuid::uuid4()->toString();
            $row->atualizado_por = Auth::user()->id;
        });

        static::created(function (CursoAtividades $row) {
            static::atualizarCurso($row->curso);
            AgendaService::agendarCurso($row);
            if ($row->alunos()->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->count() > 0) {
                foreach ($row->alunos as $aluno) {
                }
            }
        });

        static::updating(function (CursoAtividades $row) {
            Log::info('updating atividades');
            $curso = Cursos::find($row->curso_id);
            if (! $row->tipolocal_id) {
                $row->tipolocal_id = $curso->tipolocal_id;
            }
            $dirty = array_keys($row->getDirty());
            if (array_intersect(['inicio', 'termino'], $dirty)) {
                if (AgendaService::verificaConflito($row->tipolocal_id, $row->termino, $row->inicio, [], [$row->id, $row->curso_id])) {
                    Log::info("Conflito de agenda ao atualizar atividade para o curso {$curso->id}");

                    return false;
                }
            }

            if ($row->isDirty('tipolocal_id')) {
                $agendas = $row->agendas()->get();
                $agendas->each(function ($agenda) use ($row) {
                    $agenda->local_id = $row->tipolocal_id;
                    $agenda->atualizado_por = Auth::user()->id;
                    $agenda->save();
                });
            }

            Log::info('updating atividades');

            $row->atualizado_por = Auth::user()->id;
        });

        static::updated(function (CursoAtividades $row) {
            if ($row->wasChanged(['inicio', 'termino', 'carga_horaria', 'certificavel'])) {
                static::atualizarCurso($row->curso);
            }
        });

        static::deleted(function (CursoAtividades $row) {
            static::atualizarCurso($row->curso);

            $recipient = Auth::user();

            Notification::make()
                ->title('Atividade excluída corretamente!')
                ->sendToDatabase($recipient);
        });

        static::deleting(function (CursoAtividades $row) {
            if ($row->agendas()->count() > 0) {
                $agenda = $row->agendas()->get();
                foreach ($agenda as $a) {
                    Log::info("Removendo agendas {$a->inicio} da atividade {$row->titulo}");

                    $a->delete();
                }
            }
            if ($row->alunos()->count() > 0) {
                foreach ($row->alunos as $a) {
                    Log::info("Removendo aluno: {$a->aluno->name}");
                    $a->delete();
                }
            }
            if ($row->certificados()->count() > 0) {
                foreach ($row->certificados as $c) {
                    $c->delete();
                }
            }
        });
    }
}
