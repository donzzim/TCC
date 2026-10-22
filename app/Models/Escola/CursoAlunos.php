<?php

namespace App\Models\Escola;

use App\Models\Cadastro\Tabelas;
use App\Models\User;
use App\Services\MoodleService;
use App\Services\Pedagogico\FrequenciaService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/** @property string|null $uuid */
class CursoAlunos extends Model
{
    protected $table = 'esc_cursoalunos';

    protected $fillable = [
        'uuid',
        'curso_id',
        'aluno_id',
        'situacaoinscricao_id',
        'atualizado_por',
        'data_inscricao',
        'observacao',
        'aprovado',
    ];

    protected function casts(): array
    {
        return [
            'aprovado' => 'boolean',
            'data_inscricao' => 'datetime',
        ];
    }

    public function aprovar(): bool
    {
        try {
            $titulo = $this->curso->titulo ?? $this->atividade->titulo;
            Log::info("aprovando aluno {$this->aluno->name} no cursorrrr {$titulo}.");
            if ($this->update(['aprovado' => true])) {
                Notification::make()
                    ->title('Aprovação')
                    ->body("Você foi aprovado no curso: {$titulo}")
                    ->success()
                    ->sendToDatabase($this->aluno);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error("Erro {$e->getMessage()} na linha: {$e->getLine()} do arquivo: {$e->getFile()} ao aprovar aluno {$this->aluno->name}no curso");

            return false;
        }
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(CursoAtividades::class, 'curso_id', 'id');
    }

    public function situacaoinscricao(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'situacaoinscricao_id', 'id');
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(CursoFrequencias::class, 'curso_id', 'curso_id')
            ->where('aluno_id', $this->aluno_id);
    }

    protected static function booted(): void
    {
        static::creating(function (CursoAlunos $row) {
            // $row->curso_id = $row->atividade->id;
            $row->uuid = Uuid::uuid4()->toString();
            if (! $row->atualizado_por) {
                $row->atualizado_por = Auth::id();
            }
        });

        static::updating(function (CursoAlunos $row) {
            // $row->uuid = Uuid::uuid4();
            if (! $row->atualizado_por) {
                $row->atualizado_por = Auth::id();
            }
        });

        static::created(function (CursoAlunos $row) {
            // if ($row->curso->modalidade_id == config('settings.modalidade.ead') && $row->situacaoinscricao_id == config('settings.inscricao.inscrito')) { // EAD
            //     app(MoodleService::class)->matricularAlunoCurso($row); // alunos
            // }
        });

        static::updated(function (CursoAlunos $row) {
            // if ($row->curso->modalidade_id == config('settings.modalidade.ead')) { // EAD
            //     if ($row->situacaoinscricao_id == config('settings.inscricao.inscrito')) {
            //         app(MoodleService::class)->matricularAlunoCurso($row); // alunos
            //     } else {
            //         app(MoodleService::class)->removerAlunoCurso($row);
            //     }
            // }
        });

        static::deleting(function (CursoAlunos $row) {
            FrequenciaService::limparFrequencias($row);
        });

        static::deleted(function (CursoAlunos $row) {
            $curso = $row->curso()->first();
            if ($curso && $curso->modalidade_id == config('settings.modalidade.ead')) {
                app(MoodleService::class)->removerAlunoCurso($row);
            }
        });
    }
}
