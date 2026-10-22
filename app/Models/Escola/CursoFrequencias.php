<?php

namespace App\Models\Escola;

use App\Models\Administrativo\Agenda;
use App\Models\Cadastro\Tabelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CursoFrequencias extends Model
{
    protected $table = 'esc_cursofrequencias';
    protected $fillable = [
        'curso_id',
        'aluno_id',
        'agenda_id',
        'situacaofrequencia_id',
        'atualizado_por',
        'data_frequencia',
        'observacao',
    ];


    public function presente($user = null)
    {
        $this->update([
            'situacaofrequencia_id' => config('settings.frequencia.presente'),
            'atualizado_por' => $user->id ?? Auth::user()->id,
        ]);
    }
    public function ausente()
    {
        $this->update([
            'situacaofrequencia_id' => config('settings.frequencia.ausente'),
            'atualizado_por' => Auth::user()->id,
        ]);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    public function data(): BelongsTo
    {
        return $this->belongsTo(Agenda::class, 'agenda_id', 'id');
    }
    public function atividade(): BelongsTo
    {
        return $this->belongsTo(CursoAtividades::class, 'curso_id', 'id');
    }
    public function situacaofrequencia(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'situacaofrequencia_id', 'id');
    }
    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id', 'id');
    }
    public function curso_aluno()
    {
        return CursoAlunos::where('aluno_id', $this->aluno_id)->where('curso_id', $this->curso_id)->first();
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (CursoFrequencias $row) {
            if (!$row->situacaofrequencia_id) {
                $row->situacaofrequencia_id = config('settings.frequencia.ausente');
            }
            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::id() ?? $row->aluno_id;
            }
        });

        static::updating(function (CursoFrequencias $row) {
            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::id() ?? $row->aluno_id;
            }
            $row->data_frequencia = $row->data->inicio;
        });
        static::updated(function (CursoFrequencias $row) {});

        static::created(function (CursoFrequencias $row) {
            Log::info("Frequência criada: {$row->id} para o aluno {$row->aluno_id} no curso {$row->curso_id}");
        });
    }
}
