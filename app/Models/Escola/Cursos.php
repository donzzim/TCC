<?php

namespace App\Models\Escola;

use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Cadastro\Tabelas;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Setores;
use App\Models\Tags;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\MoodleService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string|null $titulo
 * @property string|null $justificativa
 * @property string|null $objetivo_geral
 * @property string|null $conteudo_programatico
 * @property string|null $abordagem_pedagogica
 * @property string|null $avaliacao_cursistas
 * @property string|null $referencias_bibliograficas
 * @property string|null $curriculo_resumido
 * @property-read Instituicoes|null $instituicao
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CursoComentarios> $comentarios
 */
class Cursos extends Model
{
    protected $table = 'esc_cursos';

    protected $fillable = [
        'legado_id',
        'uuid',
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
        'auto_config',
        'auto',
        'auto_steps',
        'auto_status',
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
        'link',
        'webnar_password',
        'inicio_tolerancia',
        'termino_tolerancia',
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
            'auto_config' => 'array',
            'auto_steps' => 'array',
            'auto_status' => 'array',
            'auto' => 'boolean',
            'termino' => 'datetime',
            'inicio_inscricao' => 'datetime',
            'termino_inscricao' => 'datetime',
            'externo' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /*
    ** Attributes
    */
    public function getInicio(): CarbonInterface
    {
        return $this->inicio;
    }

    public function getTermino(): CarbonInterface
    {
        return $this->termino;
    }

    protected function getEadAttribute()
    {

        if ($this->moodle_course_id) {
            return asset('images/moodle_logo.png');
        }

        return null;
    }

    public function alunos_para_aprovar(): bool
    {
        if ($this->alunos()->where('aprovado', false)->count() > 0) {
            return true;
        } elseif ($this->atividades()->count() > 0) {
            $atividades = $this->atividades()->get();
            foreach ($atividades as $atividade) {
                if ($atividade->alunos()->where('aprovado', false)->count() > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id', 'id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            Tags::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        );
    }

    public function agendas(): MorphMany
    {
        return $this->morphMany(Agenda::class, 'agendaable');
    }

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class, 'acao_id', 'id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }

    public function areatematica(): BelongsTo
    {
        return $this->belongsTo(AreaTematica::class, 'areatematica_id', 'id');
    }

    public function modalidade(): BelongsTo
    {
        return $this->belongsTo(Modalidade::class, 'modalidade_id', 'id');
    }

    public function tipoatividade(): BelongsTo
    {
        return $this->belongsTo(TipoAtividade::class, 'tipoatividade_id', 'id');
    }

    public function tipoformacao(): BelongsTo
    {
        return $this->belongsTo(TipoAtividade::class, 'tipoatividade_id', 'id');
    }

    public function tipoevento(): BelongsTo
    {
        return $this->belongsTo(TipoEvento::class, 'tipoevento_id', 'id');
    }

    public function localrealizacao(): BelongsTo
    {
        return $this->belongsTo(LocalRealizacao::class, 'realizacao_id', 'id');
    }

    public function tipoLocal(): BelongsTo
    {
        return $this->belongsTo(TipoLocal::class, 'tipolocal_id', 'id');
    }

    public function temAtividades(): bool
    {
        return $this->atividades()->count() > 0;
    }

    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Instituicoes::class, 'instituicao_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function userInstrutores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'esc_instrutores', 'curso_id', 'instrutor_id')
            ->using(Instrutores::class)
            ->withPivot('curso_id', 'instrutor_id', 'atualizado_por', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    public function instrutores(): HasMany
    {
        return $this->hasMany(Instrutores::class, 'curso_id', 'id');
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(CursoCertificados::class, 'curso_id', 'id');
    }

    public function certMorph(): MorphMany
    {
        return $this->morphMany(CursoCertificados::class, 'certificable');
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Tabelas::class, 'esc_recursos', 'curso_id', 'tiporecurso_id');
    }

    public function publico(): BelongsToMany
    {
        return $this->belongsToMany(TipoPublico::class, 'esc_cursopublico', 'curso_id', 'tipopublico_id');
    }

    public function comunicacao(): BelongsToMany
    {
        return $this->belongsToMany(Comunicacao::class, 'esc_comunicacaocurso', 'comunicacao_id', 'curso_id')
            ->withTimestamps()
            ->select('esc_cursos.*');
    }

    /** @return HasMany<CursoComentarios, $this> */
    public function comentarios(): HasMany
    {
        return $this->hasMany(CursoComentarios::class, 'curso_id', 'id');
    }

    public function alunos(): HasMany
    {
        return $this->hasMany(CursoAlunos::class, 'curso_id', 'id');
    }

    public function avaliacao(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'curso_id', 'id');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(CursoFrequencias::class, 'curso_id', 'id');
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(CursoArquivos::class, 'curso_id', 'id');
    }

    public function cartaz(): HasOne
    {
        return $this->hasOne(CursoArquivos::class, 'curso_id', 'id')->oldestOfMany();
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFuncional::class, 'curso_id', 'id');
    }

    public function alunosUser(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'esc_cursoalunos',
            'curso_id',
            'aluno_id'
        )->distinct();
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(CursoAtividades::class, 'curso_id', 'id');
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class, 'curso_id', 'id');
    }

    public function processosCursos(): HasMany
    {
        return $this->hasMany(ProcessoCursos::class, 'curso_id', 'id');
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

    protected static function booted(): void
    {
        static::addGlobalScope('cursos', function (Builder $builder) {
            $builder->where('esc_cursos.id', '>', 0)->whereNull('esc_cursos.curso_id');
        });

        static::creating(function (Cursos $curso) {

            if (
                CursoAtividades::where('curso_id', '=', $curso->id)->count() <= 0
                && filled($curso->inicio)
                && filled($curso->termino)
            ) {
                if (AgendaService::verificaConflito($curso->tipolocal_id, $curso->termino, $curso->inicio, [], [$curso->id])) {
                    Log::info("Conflito de agenda ao criar curso {$curso->id} sem atividades.");

                    return false;
                }
            }

            $curso->uuid = Uuid::uuid4()->toString();
            $curso->ano = Carbon::parse($curso->inicio)->year;
            $curso->atualizado_por = Auth::id();
        });

        static::updating(function (Cursos $curso) {
            $curso->atualizado_por = Auth::id();

            if ($curso->liberar_inscricao && $curso->isDirty('liberar_inscricao')) {

                if (($curso->modalidade_id == config('settings.modalidade.remoto') || $curso->modalidade_id == config('settings.modalidade.hibrido')) && ! $curso->link) {
                    $curso->liberar_inscricao = false;
                    Notification::make()
                        ->title('Cadastro Incompleto')
                        ->body('Link de reunião/webinar é obrigatório para cursos remotos/híbridos antes de liberar inscrições.')
                        ->warning()
                        ->persistent()
                        ->send();
                }
            }

            if (
                ($curso->isDirty('inicio') || $curso->isDirty('termino') || $curso->isDirty('tipolocal_id'))
                && filled($curso->inicio)
                && filled($curso->termino)
            ) {
                if (! $curso->temAtividades()) {
                    if (AgendaService::verificaConflito(
                        $curso->tipolocal_id,
                        $curso->termino,
                        $curso->inicio,
                        [],
                        [$curso->id]
                    )) {
                        Log::info("Conflito de agenda ao atualizar curso {$curso->id} sem atividades.");

                        return false;
                    }
                }
                if ($curso->isDirty('tipolocal_id') && ! $curso->temAtividades()) {
                    $agendas = $curso->agendas()->get();
                    $agendas->each(function ($agenda) use ($curso) {
                        $agenda->local_id = $curso->tipolocal_id;
                        $agenda->atualizado_por = Auth::id();
                        $agenda->save();
                    });
                }
            }
            if ($curso->isDirty('ativo')) {
                $curso->atividades()->update([
                    'ativo' => $curso->ativo,
                    'atualizado_por' => Auth::id(),
                ]);
            }
        });

        static::deleting(function (Cursos $curso) {
            $relationMethods = ['instrutores', 'recursos', 'publico', 'comentarios', 'atividades', 'arquivos', 'registros'];

            if ($curso->agendas()->count() > 0) {
                $agenda = $curso->agendas()->get();
                foreach ($agenda as $a) {
                    $a->delete();
                }
            }

            if ($curso->instrutores()->count() > 0) {
                $instrutores = $curso->instrutores()->get();
                foreach ($instrutores as $i) {
                    $i->delete();
                }
            }

            if ($curso->registros()->count() > 0) {
                $registros = $curso->registros()->get();
                foreach ($registros as $r) {
                    $r->delete();
                }
            }

            // deletar todos os relacionamentos com atividades alunos frequencias
            foreach ($relationMethods as $relationMethod) {
                if ($curso->$relationMethod()->count() > 0) {
                    $relacionado = $curso->$relationMethod()->get();
                    Notification::make()
                        ->title('Não é possível deletar o curso')
                        ->body('O curso '.$curso->titulo.' está vinculado a '.count($relacionado).' registros e não pode ser deletado.')
                        ->warning()
                        ->send();

                    return false;
                }
            }
        });

        static::created(function (Cursos $curso) {
            Log::info("Curso created: {$curso->id} - {$curso->titulo}");
            if ($curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                app(MoodleService::class)->syncCurso($curso);
            }
            if (! $curso->temAtividades() && filled($curso->inicio) && filled($curso->termino)) {

                AgendaService::agendarCurso($curso);
                Log::info("Agenda criada para o curso no created {$curso->id}");
            }
        });

        static::updated(function (Cursos $curso) {
            if ($curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                app(MoodleService::class)->syncCurso($curso);
            }
            Log::info("Curso updated: {$curso->id} - {$curso->titulo}");
        });

        static::deleted(function (Cursos $curso) {
            if ($curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
                if ($curso->moodle_course_id) {
                    app(MoodleService::class)->excluirCurso($curso->moodle_course_id);
                }
            }
        });
    }
}
