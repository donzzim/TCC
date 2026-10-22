<?php

namespace App\Models\Administrativo;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Cadastro\Tabelas;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use App\Notifications\SendNotifications;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Notification;

class RegistroFuncional extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;
    protected $table = 'adm_registrofuncional';
    protected $fillable = [
        'tipo_id',
        'pessoa_id',
        'curso_id',
        'atividade_id',
        'demanda_id',
        'atualizado_por',
        'inicio',
        'termino',
        'quantidade',
        'processo',
        'situacao',
        'observacao',
    ];
    protected function casts(): array
    {
        return [
            'situacao' => 'boolean',
        ];
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'tipo_id', 'id');
    }
    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pessoa_id', 'id');
    }
    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id', 'id');
    }
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'curso_id', 'id');
    }
    public function atividade(): BelongsTo
    {
        return $this->belongsTo(CursoAtividades::class, 'atividade_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::updating(function (RegistroFuncional $row) {
            // Notification::send(Auth::user(), new SendNotifications($row));
            $user = Auth::user();
            Notification::make()
                ->title('Registro autalizado corretamente!')
                ->sendToDatabase($user);

            $row->atualizado_por = Auth::user()->id;
        });
    }
}
