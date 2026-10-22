<?php

namespace App\Models\Escola;

use App\Models\User;
use App\Services\Pedagogico\CertificadoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class CursoCertificados extends Model
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
        'conteudo',
        'disponivel',
        'externo',
        'validado',
        'validado_por',
        'validated_at',
        'observacao',
    ];
    protected function casts(): array
    {
        return [
            'externo' => 'boolean',
            'validated_at' => 'datetime',
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
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    protected static function validarVinculoExterno(CursoCertificados $row): void
    {
        if (! $row->externo || ! $row->pessoa_id || ! $row->certificable_id) {
            return;
        }

        $evento = match ($row->certificable_type) {
            Cursos::class => Cursos::find($row->certificable_id),
            CursoAtividades::class => CursoAtividades::find($row->certificable_id),
            default => null,
        };

        if (! $evento) {
            return;
        }

        CertificadoService::validarVinculo(
            (int) $evento->id,
            (int) $row->pessoa_id,
            false,
            $evento,
        );
    }

    protected static function validarAprovacaoInterna(CursoCertificados $row): void
    {
        if ($row->externo || ! $row->pessoa_id || ! $row->certificable_id) {
            return;
        }

        if (! in_array($row->certificable_type, [Cursos::class, CursoAtividades::class], true)) {
            return;
        }

        if (! CertificadoService::validarAprovacao((int) $row->certificable_id, (int) $row->pessoa_id)) {
            throw ValidationException::withMessages([
                'pessoa_id' => 'O participante precisa estar aprovado no curso/atividade antes da emissÃ£o do certificado.',
            ]);
        }
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

        static::creating(function (CursoCertificados $row) {
            $row->uuid = Uuid::uuid4()->toString();
            $row->atualizado_por = Auth::id() ?? 1;
            static::validarAprovacaoInterna($row);
            static::validarVinculoExterno($row);
        });
        static::created(function (CursoCertificados $row) {});


        static::updating(function (CursoCertificados $row) {
            $row->atualizado_por = Auth::id() ?? 1;
            static::validarVinculoExterno($row);
        });

        static::deleting(function (CursoCertificados $row) {});
    }
}
