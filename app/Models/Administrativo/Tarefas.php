<?php

namespace App\Models\Administrativo;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use App\Services\TarefaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Tarefas extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;
    protected $table = 'adm_tarefas';
    protected $fillable = [
        'setor_id',
        'demanda_id',
        'status_id',
        'list_id',
        'card_id',
        'registrofuncional_id',
        'status_id',
        'atualizado_por',
        'descricao',
        'cor',
        'data_iniciada',
        'data_concluida',
        'ativo',
        'json',
    ];
    protected $casts = [
        'json' => 'array', // <--- Importante para converter JSON <-> Array
        'data_iniciada' => 'datetime',
        'data_concluida' => 'datetime',
        'ativo' => 'boolean'
    ];
    public function getContextoAttribute()
    {
        if (!$this->demanda) {
            return "Tarefa Avulsa";
        }

        $tipoId = $this->demanda->tipodemanda_id;

        // Tenta buscar o curso e processo vinculados à demanda
        // Nota: Em Demanda.php a relação é hasMany(cursos), pegamos o primeiro para contexto
        $curso = $this->demanda->cursos()->first();
        $processo = $curso ? $curso->processos()->first() : null; // Assume relação via curso

        // Regra 1: Pedagógica (3102) e Contratação (3103) -> SEMPRE Processos e Cursos
        if (in_array($tipoId, [3102, 3103])) {
            if ($processo && $curso) {
                return "Proc: {$processo->numero} | Curso: {$curso->titulo}";
            }
            if ($curso) {
                return "Curso: {$curso->titulo} (Sem Processo)";
            }
            return "Demanda: {$this->demanda->titulo} (Dados incompletos)";
        }

        // Regra 2: Administrativa (3101) -> PODE ter Processo ou NÃO
        if ($tipoId == 3101) {
            // Se tiver vínculo com processo (ex: via um curso administrativo ou lógica futura)
            if ($processo) {
                return "Proc: {$processo->numero} | {$this->demanda->titulo}";
            }
            // Caso contrário, mostra apenas a Demanda
            return "{$this->demanda->titulo}";
        }

        // Padrão para outros tipos
        return $this->demanda->titulo;
    }

    public function getTipoContextoAttribute()
    {
        if ($this->demanda && $this->demanda->tipodemanda_id) {
            // IDs baseados na sua descrição
            return match ($this->demanda->tipodemanda_id) {
                3101 => 'Administrativa',
                3102 => 'Pedagógica',
                3103 => 'Contratação',
                3104 => 'Agendamento',
                default => 'Geral'
            };
        }
        return 'Avulsa';
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusTarefa::class, 'status_id', 'id');
    }
    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id', 'id');
    }
    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroFuncional::class, 'registrofuncional_id', 'id');
    }
    public function list(): BelongsTo
    {
        return $this->belongsTo(Lists::class, 'list_id', 'id');
    }
    public function card(): BelongsTo
    {
        return $this->belongsTo(Cards::class, 'card_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function (Tarefas $row) {
            // $row->ativo = true;
            $row->atualizado_por = Auth::user()->id;
            // $row->status = config('settings.situacaotarefa.pendente');
            if ($row->registrofuncional_id) {
                $row->ativo = true;
                $row->status_id = config('settings.situacaotarefa.pendente');
            }
        });
        static::updating(function (Tarefas $row) {
            $row->atualizado_por = Auth::user()->id;
            $original = $row->getOriginal();
            $original = new Tarefas($original);
            TarefaService::gerenciarTarefas($row, $original);
        });
    }
}
