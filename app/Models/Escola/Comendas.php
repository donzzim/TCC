<?php

namespace App\Models\Escola;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class Comendas extends Model
{
    protected $table = 'esc_cursocertificados';
    protected $fillable = [
        'uuid',
        'curso_id',
        // 'cursoatividade_id',
        'pessoa_id',
        'carga_horaria',
        'certificable_id',
        'certificable_type',
        'modelo_id',
        'assinado_por',
        'atualizado_por',
        'titulo',
        'nome',
        'arquivo',
        'disponivel',
        'observacao',
    ];
    protected function casts(): array
    {
        return [
            'disponivel' => 'boolean',
        ];
    }
    // public function curso(): BelongsTo
    // {
    //     return $this->belongsTo(Cursos::class, 'curso_id','id');
    // }
    // public function atividade(): BelongsTo
    // {
    //     return $this->belongsTo(CursoAtividades::class, 'cursoatividade_id','id');
    // }

    public function certificable(): MorphTo
    {
        return $this->morphTo();
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    public function atividade(): BelongsTo
    {
        return $this->belongsTo(CursoAtividades::class, 'curso_id', 'id');
    }
    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pessoa_id', 'id');
    }
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(CertificadoModelos::class, 'modelo_id', 'id');
    }
    public function assinado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    protected static function booted(): void
    {
        // \Illuminate\Support\Facades\DB::listen(function ($query) {
        //     // salvar somente deletes na tabela de certificados
        //     if (stripos($query->SQL, 'esc_cursocertificados') !== false) {
        //         \Illuminate\Support\Facades\Log::info('SQL ESC_CERTIFICADOS', [
        //             'SQL' => $query->SQL,
        //             'bindings' => $query->bindings,
        //             'time' => $query->time,
        //         ]);
        //     }
        // });

        static::addGlobalScope('comendas', function (Builder $builder) {
            $builder->where('certificable_type', self::class);
        });


        static::creating(function (Comendas $row) {
            Log::info("Creating Comendas carga_horaria set to {$row->carga_horaria}");
            // dd('creating');
            $row->uuid = Uuid::uuid4()->toString();
            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::user()->id ?? $row->pessoa_id;
            }
            Log::info("Creating Comendas atualizado_por set to {$row->atualizado_por}");
        });
        static::created(function (Comendas $row) {
            Log::info("Created Comendas with ID {$row->id}");
        });


        static::updating(function (Comendas $row) {

            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::user()->id ?? $row->pessoa_id;
            }
        });

        static::deleting(function (Comendas $row) {});
    }
}
